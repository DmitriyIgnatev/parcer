<?php
set_time_limit(60000);
// включаем вывод ошибочек
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// включаем замер исполнения скрипта
// подключаем prolog bitrix
require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php';
// подключаем нужные модули
CModule::IncludeModule("iblock");

$IBLOCK_ID = 65;
$reader = new XMLReader();

// $url = "https://bior-opt.ru/export/catalog_do.php?type=xml&sections%5B%5D=3802login=79057677670@yandex.ru&password=Ss01253489!!";

// $url = "https://bior-opt.ru/export/catalog_do.php?type=xml&sections%5B%5D=3780&sections%5B%5D=3812&login=79057677670@yandex.ru&password=Ss01253489!!";
$url = "https://bior-opt.ru/export/catalog_do.php?type=xml&login=79057677670@yandex.ru&password=Ss01253489!!";

// Локальный файл, в который будет скачан XML
$localFile = "export_catalog.xml";

// Инициализируем cURL
$ch = curl_init($url);

// Открываем файл для записи
$fp = fopen($localFile, 'w');

// Настраиваем cURL для записи данных в файл
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

// Выполняем запрос
curl_exec($ch);

// Закрываем cURL сессию и файл
curl_close($ch);
fclose($fp);

echo "Файл успешно скачан и сохранен как $localFile";


$reader->open($localFile); // указываем ридеру что будем парсить этот файл

// циклическое чтение документа
$data = array();
$element = array();
$key_value = array();
$count = 1;
$t = 0;
$storeId = 1; // id склада
$prop_array = array();
$arProp = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $IBLOCK_ID));
while ($prop = $arProp->GetNext()) {
    $prop_array[$prop["NAME"]] = $prop["ID"];
}

$sectionArray = array();
$c = 1;
CModule::IncludeModule('iblock');
$arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'GLOBAL_ACTIVE' => 'Y', "SECTION_ID" => false);
$arSelect = array('ID', 'NAME', 'IBLOCK_ID ', 'IBLOCK_SECTION_ID ');
$resSection = CIBlockSection::GetList(array(), $arFilter, true, $arSelect);
while ($elem = $resSection->GetNext()) {
    $section_list_child = array();
    $sectionArray[$elem['NAME']] = array();
    $arFilter_child = array('IBLOCK_ID' => $IBLOCK_ID, 'GLOBAL_ACTIVE' => 'Y', "SECTION_ID" => $elem['ID']);
    $resSection_child = CIBlockSection::GetList(array(), $arFilter_child, true, $arSelect);
    while ($section = $resSection_child->GetNext()) {
        $sectionArray[$elem['NAME']] += array($c => $section);

        $c++;
    }
}
// echo "<pre>" . print_r($sectionArray) . '</pre> <br>';


while ($reader->read()) {
    $elem_key = '';
    $elem_value = '';
    while ($reader->read()) {
        if ($reader->localName == 'product') {
            if ($element == null) {
                continue;
            }
            $el = new CIBlockElement;
            $PROP = array();
            foreach ($element as $key => $value) {
                // if ($fruit_name == 'apple') {
                //     echo key($array), "\n";
                // }
                // echo '<pre>' . $key . '-' . $value . '</pre>';
                $key = str_replace('_', ' ', $key);
                if (key_exists($key, $prop_array) == true) {
                    $PROP[$prop_array[$key]] = $value;
                }
                // next($element);
            }

            // echo '<pre>' . print_r($PROP) . $element['Наименование'] . '</pre>';
            // echo '<pre>' . print_r($element) . '</pre>';
            $data += array($count => $element);


            $section_id = 0;
            $flag = false;
            foreach ($sectionArray as $key => $value) {
                if ($element["Назначение_основное"] == '! УЦЕНКА') {
                    $section_id = 434;
                    $flag = true;
                    break;
                }
                if ($element["Назначение_основное"] == 'КОСМЕТИКА ДЛЯ ЛИЦА И ТЕЛА') {
                    $section_id = 453;
                    $flag = true;
                    break;
                }
                if ($element["Назначение_основное"] == 'НАБОРЫ') {
                    $section_id = 449;
                    $flag = true;
                    break;
                }
                if (
                    $element["Назначение_основное"] == 'СРЕДСТВА ДЛЯ ОБРАБОТКИ ИГРУШЕК'
                ) {
                    $section_id = 443;
                    $flag = true;
                    break;
                }
                if (
                    $element["Назначение_основное"] == 'СРЕДСТВА ГИГИЕНЫ'
                ) {
                    $section_id = 442;
                    $flag = true;
                    break;
                }
                if (
                    $element["Назначение_основное"] == 'ЭКСТЕНДЕРЫ, ТРЕНАЖЕРЫ, ЭЛЕКТРОСТИМУЛЯТОРЫ'
                ) {
                    $section_id = 435;
                    $flag = true;
                    break;
                }
                if (
                    $element["Назначение_основное"] == 'СУВЕНИРЫ, УПАКОВКА'
                ) {
                    $section_id = 442;
                    $flag = true;
                    break;
                }
                if ($element["Назначение_основное"] == $key) {
                    $section_id = array();
                    foreach ($value as $key_value => $value_value) {
                        if ($value_value['NAME'] == $element['Назначение1']) {
                            $section_id[] = $value_value['ID'];
                            $flag = true;
                        }
                        if ($value_value['NAME'] == $element['Назначение2']) {
                            $section_id[] = $value_value['ID'];
                            $flag = true;
                        }
                        if ($value_value['NAME'] == $element['Назначение3']) {
                            $section_id[] = $value_value['ID'];
                            $flag = true;
                        }
                        if ($value_value['NAME'] == $element['Назначение4']) {
                            $section_id[] = $value_value['ID'];
                            $flag = true;
                        }
                    }
                }
                if ($flag) {
                    break;
                }
            }
            $num = str_replace(' ', '', $element['Цена']);
            $num = floatval(str_replace(',', '.', $num));
            $num = intval($num);
            // echo print_r($section_id) . "-" . $element['Наименование'] . '|' . $num * 3 . "<br>";

            foreach ($section_id as $section_main):
                $arLoadProductArray = array(
                    "MODIFIED_BY" => $USER->GetID(), // элемент изменен текущим пользователем
                    "IBLOCK_SECTION_ID" => $section_main,          // элемент лежит в корне раздела
                    "IBLOCK_ID" => $IBLOCK_ID,
                    "PROPERTY_VALUES" => $PROP,
                    "NAME" => $element['Наименование'],

                    "ACTIVE" => "Y",            // активен
                    "PREVIEW_TEXT" => $element['Описание'],
                    "DETAIL_TEXT" => $element['Описание'],
                    "DETAIL_PICTURE" => CFile::MakeFileArray($element['Изображение']),
                    "PREVIEW_PICTURE" => CFile::MakeFileArray($element['Изображение']),
                    // "QUANTITY" => 100,
                    // "AVAILABLE" => 100,
                    // "WEIGHT" => $element['Вес_г'],
                    // "WIDTH" => $element['Ширина_диаметр_мм'],
                    // "LENGHT" => $element['Общая_длина_изделия_мм'],
                    // "PRICE" => $element['Цена'] * 3

                );

                if ($PRODUCT_ID = $el->Add($arLoadProductArray))
                    echo "New ID: " . $PRODUCT_ID;
                else
                    echo "Error: " . $el->LAST_ERROR;
                $productFileds = array(
                    // ID добавленного элемента инфоблока
                    "ID" => $PRODUCT_ID,
                    // выставляем тип ндс (задается в админке)
                    "VAT_ID" => 1,
                    // НДС входит в стоимость
                    "VAT_INCLUDED" => "Y",
                    // тип товара
                    "TYPE " => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT
                );
                if (CCatalogProduct::Add($productFileds)) {
                    // элемент инфоблока превращен в товар
                } else {
                    // произошла ошибка
                }


                $rsPrices = \Bitrix\Catalog\GroupTable::getList();
                while ($arPrice = $rsPrices->fetch()) {
                    $PRICE_IDS[] = $arPrice['ID'];
                }
                $dbPrice = \Bitrix\Catalog\Model\Price::getList([
                    "filter" => array(
                        "PRODUCT_ID" => $PRODUCT_ID,
                        "CATALOG_GROUP_ID" => 1
                    )
                ]);
                if ($arPrice = $dbPrice->fetch()) {
                    $price = $arPrice['PRICE'];
                }
                // -------
                $arFieldsPrice = array(
                    // ID добавленного товара
                    "PRODUCT_ID" => $PRODUCT_ID,
                    // ID типа цены
                    "CATALOG_GROUP_ID" => 1,
                    // значение цены
                    "PRICE" => $num * 3,
                    // валюта
                    "CURRENCY" => !$currency ? "RUB" : $currency,
                );
                // смотрим установлена ли цена адля данного товара
                $dbPrice = \Bitrix\Catalog\Model\Price::getList([
                    "filter" => array(
                        "PRODUCT_ID" => $PRODUCT_ID,
                        "CATALOG_GROUP_ID" => 1
                    )
                ]);
                if ($arPrice = $dbPrice->fetch()) {
                    // если цена установлена, то обновляем
                    $result = \Bitrix\Catalog\Model\Price::update($arPrice["ID"], $arFieldsPrice);
                    if ($result->isSuccess()) {
                        echo "Обновили цену у товара у элемента каталога " . $element['Наименование'] . " Цена " . $num * 3 . PHP_EOL;
                    } else {
                        echo "Ошибка обновления цены у товара у элемента каталога " . $element['Наименование'] . " Ошибка " . $result->getErrorMessages() . PHP_EOL;
                    }
                } else {
                    // если цены нет, то добавляем
                    $result = \Bitrix\Catalog\Model\Price::add($arFieldsPrice);
                    if ($result->isSuccess()) {
                        echo "Добавили цену у товара у элемента каталога " . $element['Наименование'] . " Цена " . $num * 3 . PHP_EOL;
                    } else {
                        echo "Ошибка добавления цены у товара у элемента каталога " . $element['Наименование'] . " Ошибка " . $result->getErrorMessages() . PHP_EOL;
                    }
                }

                $arFields = array(
                    // ID товара
                    "PRODUCT_ID" => $PRODUCT_ID,
                    // ID склада
                    "STORE_ID" => $storeId,
                    // количество
                    "AMOUNT" => 100, // По умолчанию 100
                );
                CCatalogStoreProduct::Add($arFields);

                $rs = CCatalogStoreProduct::GetList(false, array(
                    // ID товара
                    'PRODUCT_ID' => $PRODUCT_ID,
                    // ID склада
                    'STORE_ID' => $storeId
                ));
                while ($ar_fields = $rs->GetNext()) {
                    // обновим значение остатка на складе из значения остатка количественного учёта
                    $arFields = array(
                        // ID товара
                        "PRODUCT_ID" => $PRODUCT_ID,
                        // ID склада
                        "STORE_ID" => $storeId,
                        // количество
                        "AMOUNT" => 100,
                    );
                    CCatalogStoreProduct::Update($ar_fields['ID'], $arFields);
                }
                $existProduct = \Bitrix\Catalog\Model\Product::getCacheItem($arFields['ID'], true);
                if (!empty($existProduct)) {
                    \Bitrix\Catalog\Model\Product::update(intval($arFields['ID']), $arFields);
                } else {
                    \Bitrix\Catalog\Model\Product::add($arFields);
                }
                CCatalogProduct::Update(
                    // ID добавленного или обновляемого товара
                    $PRODUCT_ID,
                    array(
                        // кол-во товара
                        "QUANTITY" => 100,
                        "WEIGHT" => $element['Вес_г'],
                        "LENGHT" => $element['Общая_длина_изделия_мм'],
                        "WIDTH" => $element['Ширина_диаметр_мм']
                    )
                );

            endforeach;
            // -------


            $element = array();
            $count++;
            $t = 0;
            continue;
        }
        if ($reader->nodeType === 1) {
            $elem_key = $reader->localName;
        } else if ($reader->nodeType == XMLReader::TEXT || $reader->nodeType == XMLReader::CDATA) {
            $elem_value = $reader->value;
            if ($elem_key == 'Дополнительные_изображения') {

                $element[$elem_key]['n' . $t] = array('VALUE' => array('PATH' => CFile::MakeFileArray($elem_value)));
                //echo  print_r($element) . '<br>';
                $t++;
                continue;
            } else {
                // echo "<pre>" . print_r($element);
                // echo "<pre>" . $elem_key . ' ' . $elem_value . '<br>';
                $key_value = array();
                $key_value[$elem_key] = $elem_value;


            }

            $element += $key_value;
            // echo '<pre>' . print_r($element) . '</pre> <br>';

            $key_value = array();
        }
        ;
    }
    ;
}
?>


<pre><? // print_r($sectionArray); ?></pre>
