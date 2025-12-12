<?php

namespace MeestShipping\Modules;

use Exception;
use MeestShipping\Models\{Branch, District, Region, City, Street};
use ZipArchive;

class Dictionary implements \MeestShipping\Contracts\Module
{
    const MAX_ROWS = 100;

    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function init()
    {
        ini_set('memory_limit','-1');
        ini_set('max_execution_time', '0');

        $upload_dir = wp_upload_dir();
        $uploads_path = $upload_dir['basedir'];
        $dictionaryDir = $uploads_path . DIRECTORY_SEPARATOR . 'meest_for-woocommerce';
        $dictionaryPath = $dictionaryDir . DIRECTORY_SEPARATOR . 'dictionary.zip';

        if (!file_exists($dictionaryDir) && !wp_mkdir_p($dictionaryDir)) {
            error_log('Failed to create directory: ' . $dictionaryDir);
            throw new Exception('Failed to create directory: ' . $dictionaryDir);
        }

        $this->download($dictionaryPath, $dictionaryDir);

        foreach ($this->options['dictionary']['files'] as $dictionary => $fileName) {
            $filePath = $dictionaryDir . DIRECTORY_SEPARATOR . $dictionary.'.csv';
            if (file_exists($filePath)) {
                $this->parse($filePath, $dictionary);
            }
            unlink($filePath);
        }

        return true;
    }

    private function download(string $dictionaryPath, string $dictionaryDir)
    {
        $dictionaryData = file_get_contents($this->options['dictionary_url']);
        if ($dictionaryData === false) {
            error_log('Failed to load url: ' . $this->options['dictionary_url']);
            throw new Exception('Failed to load url: ' . $this->options['dictionary_url']);
        }

        file_put_contents($dictionaryPath, $dictionaryData);

        $zip = new ZipArchive();
        if ($zip->open($dictionaryPath) === true) {
            foreach ($this->options['dictionary']['files'] as $dictionary => $fileName) {
                $fileContent = $zip->getFromName($fileName);
                if ($fileContent !== false) {
                    file_put_contents($dictionaryDir . DS . "$dictionary.csv" , $fileContent);
                }
            }
            $zip->close();
        } else {
            error_log('Failed to open or extract the zip file.');
            throw new Exception('Failed to open or extract the zip file.');
        }
    }

    private function parse(string $filePath, string $type)
    {
        $countryUuid = $this->options['country_id']['ua'];
        $class = null;
        $columns = [];
        $values = [];

        switch ($type) {
            case 'region':
                $class = Region::class;
                $columns = ['region_uuid', 'country_uuid', 'name_uk', 'name_ru'];
                $values = function ($data) use ($countryUuid) {
                    return [$data[0], $countryUuid, self::decode($data[1]), self::decode($data[2])];
                };
                break;
            case 'district':
                $class = District::class;
                $columns = ['district_uuid', 'region_uuid', 'name_uk', 'name_ru'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[1], ['---', '*', '***']) ? [] : [$data[0], $data[3], self::decode($data[1]), self::decode($data[2])];
                };
                break;
            case 'city':
                $class = City::class;
                $columns = ['city_uuid', 'district_uuid', 'region_uuid', 'country_uuid', 'type_id', 'name_uk', 'name_ru', 'delivery_zone'];
                $values = function ($data) use ($countryUuid) {
                    // Skip only if UUID is missing
                    if (empty($data[0])) {
                        return [];
                    }
                    
                    $nameUk = self::decode($data[1] ?? '');
                    $nameRu = self::decode($data[2] ?? '');
                    
                    // If both names are empty or invalid markers, skip
                    if (($nameUk === '' || in_array($nameUk, ['---', '*', '***'])) && 
                        ($nameRu === '' || in_array($nameRu, ['---', '*', '***']))) {
                        return [];
                    }
                    
                    // Clean invalid markers
                    if (in_array($nameUk, ['---', '*', '***'])) $nameUk = '';
                    if (in_array($nameRu, ['---', '*', '***'])) $nameRu = '';
                    
                    return [
                        $data[0], 
                        $data[4] ?? '', 
                        $data[5] ?? '', 
                        $countryUuid, 
                        1, 
                        $nameUk ?: $nameRu, 
                        $nameRu ?: $nameUk, 
                        self::decode($data[7] ?? '')
                    ];
                };
                break;
            case 'street':
                $class = Street::class;
                $columns = ['street_uuid', 'city_uuid', 'type_id', 'postcode', 'name_uk', 'name_ru', 'type_uk', 'type_ru'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[3], ['---', '*', '***']) ? [] : [$data[0], $data[5], 1, self::decode($data[12] ?? ''), self::decode($data[3] ?: $data[4]), self::decode($data[4] ?: $data[3]), self::decode($data[1] ?: $data[2]), self::decode($data[2] ?: $data[1])];
                };
                break;
            case 'branch':
                $class = Branch::class;
                $columns = ['branch_uuid', 'city_uuid', 'name_uk', 'description_uk'];
                $values = function ($data) use ($countryUuid) {
                    return in_array($data[1], ['---', '*', '***']) ? [] : [$data[0], $data[3], self::decode($data[1] ?: $data[2]), self::decode($data[2]?: $data[1])];
                };
                break;
        }

        if ($class !== null) {
            self::insert($filePath, $class, $columns, $values);
        }
    }

    private static function insert(string $filePath, string $class, array $columns, \Closure $values)
    {
        if (false !== $handle = fopen($filePath, 'r')) {
            $class::truncate();

            $rowNumber = 0;
            $rows = [];
            
            while (false !== $data = fgetcsv($handle, 10000, ';')) {
                if (empty($data) || (count($data) === 1 && empty($data[0]))) {
                    continue;
                }
                
                $data = $values($data);
                if (empty($data)) {
                    continue;
                }

                $rowNumber++;
                $rows[] = $data;

                if ($rowNumber >= self::MAX_ROWS) {
                    try {
                        $class::insert($columns, $rows);
                    } catch (Exception $e) {
                        foreach ($rows as $row) {
                            try {
                                $class::insert($columns, [$row]);
                            } catch (Exception $ex) {
                                // Skip problematic rows
                            }
                        }
                    }
                    
                    $rowNumber = 0;
                    $rows = [];
                }
            }

            // Insert remaining rows
            if (!empty($rows)) {
                try {
                    $class::insert($columns, $rows);
                } catch (Exception $e) {
                    foreach ($rows as $row) {
                        try {
                            $class::insert($columns, [$row]);
                        } catch (Exception $ex) {
                            // Skip problematic rows
                        }
                    }
                }
            }

            fclose($handle);
        }
    }

    private static function decode(string $str): string
    {
        if ($str === '' || $str === null) {
            return '';
        }
        
        // Try Windows-1251 to UTF-8 conversion
        $decoded = @mb_convert_encoding($str, 'UTF-8', 'Windows-1251');
        
        // If conversion failed, try alternative methods
        if ($decoded === false || $decoded === null || $decoded === '') {
            // Try iconv as fallback
            $decoded = @iconv('Windows-1251', 'UTF-8//IGNORE', $str);
            if ($decoded === false || $decoded === '') {
                // Last resort - use original string
                $decoded = $str;
            }
        }
        
        // Remove NULL bytes
        $decoded = str_replace("\0", '', $decoded);
        
        return trim($decoded);
    }
}
