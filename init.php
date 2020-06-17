<?php
AddEventHandler("main", "OnAfterUserAdd", Array("UserNews", "OnAfterUserAddHandler"));
AddEventHandler("main", "OnBeforeUserUpdate", Array("UserNews", "OnBeforeUserUpdateHandler"));
class UserNews
{
    function OnAfterUserAddHandler(&$arFields)
    {
        if ($arFields["ID"] > 0) {
            $staff = false;
            foreach($arFields['GROUP_ID'] as $arGroup){
                if($arGroup['GROUP_ID'] == 12){
                    $staff = true;
                }
            }
            if($staff) {
                $rsUser = CUser::GetByID($arFields["ID"]);
                $arUser = $rsUser->Fetch();

                if (!CModule::IncludeModule("iblock")) die('Error Include Module «IBlock»');
                $el = new CIBlockElement;
                $params = Array(
                    "max_len" => "100", // обрезает символьный код до 100 символов
                    "change_case" => "L", // буквы преобразуются к нижнему регистру
                    "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                    "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                    "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                    "use_google" => "false", // отключаем использование google
                );

                $chief = '';
                if($arUser['WORK_NOTES']) {
                    $chief = explode('Руководитель:', $arUser['WORK_NOTES']);
                    if($chief[1]){
                        $chief = trim(str_replace(';', '', $chief[1]));
                    }else{
                        $chief = '';
                    }
                }
                $arProp = [
                    'USER_ID' => $arFields["ID"],
                    'EVENT' => 85,
                    'NEW_JOB' => $arUser['WORK_POSITION'],
                    'UNIT' => array_pop($arUser['UF_DEPARTMENT']),
                    'CHIEF'     =>  $chief,
                ];
                $full_name = $arUser['LAST_NAME'] . ' ' . $arUser['NAME'];
                if ($arUser['PERSONAL_GENDER'] == 'F') {
                    $name = $full_name . ' принята на работу';
                } else {
                    $name = $full_name . ' принят на работу';
                }
                $code = CUtil::translit($name, "ru", $params);
                $date = new DateTime();

                $arLoadProductArray = Array(
                    "IBLOCK_SECTION_ID" => false,
                    "IBLOCK_ID" => 35,
                    "PROPERTY_VALUES" => $arProp,
                    "NAME" => $name,
                    "CODE" => $code,
                    "ACTIVE" => "N",
                    "PREVIEW_TEXT" => '<p>' . $full_name . ', поздравляем с началом работы!</p>',
                    "PREVIEW_TEXT_TYPE" => 'html',
                    'DATE_ACTIVE_FROM' => $date->format('d.m.Y'),
                );
                if (!$PRODUCT_ID = $el->Add($arLoadProductArray)) {
                    $arFilter = array("IBLOCK_ID" => 35, "%CODE" => $code);
                    $res = CIBlockElement::GetList(['ID' => 'ASC'], $arFilter, false, false, array("ID", "IBLOCK_ID", "CODE"));
                    while ($arItem = $res->GetNext(true, false)) {
                        $code = $arItem['CODE'];
                    }
                    $arCode = explode('-', $code);
                    $count = (int)$arCode[1] + 1;
                    $code = $arCode[0] . '-' . $count;
                    $arLoadProductArray['CODE'] = $code;
                    if (!$PRODUCT_ID = $el->Add($arLoadProductArray)) {
                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/.log/add_news-staff_' . date('y-m-d_H-i-s') . '-error.log', var_export(['ERROR' => $el->LAST_ERROR], true));
                    }
                }
                if($PRODUCT_ID) CEvent::Send("NEWS_STAFF", "s1", ['ID'=>$PRODUCT_ID,'EVENT'=>'Новый сотрудник','NAME'=>$full_name]);
            }
        }
    }

    function OnBeforeUserUpdateHandler(&$arFields)
    {
        if($arFields['ID'] && $arFields['WORK_POSITION'] && $arFields['GROUP_ID']){
            $staff = false;
            foreach($arFields['GROUP_ID'] as $arGroup){
                if($arGroup['GROUP_ID'] == 12){
                    $staff = true;
                }
            }
            if($staff){
                $rsUser = CUser::GetByID($arFields["ID"]);
                $arUser = $rsUser->Fetch();

                if($arFields['WORK_POSITION'] != $arUser['WORK_POSITION']) {
                    if (!CModule::IncludeModule("iblock")) die('Error Include Module «IBlock»');
                    $el = new CIBlockElement;
                    $params = Array(
                        "max_len" => "100", // обрезает символьный код до 100 символов
                        "change_case" => "L", // буквы преобразуются к нижнему регистру
                        "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                        "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                        "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                        "use_google" => "false", // отключаем использование google
                    );

                    $chief = '';
                    if ($arFields['WORK_NOTES']) {
                        $chief = explode('Руководитель:', $arFields['WORK_NOTES']);
                        if ($chief[1]) {
                            $chief = trim(str_replace(';', '', $chief[1]));
                        } else {
                            $chief = '';
                        }
                    }
                    $arProp = [
                        'USER_ID' => $arFields["ID"],
                        'EVENT' => 84,
                        'NEW_JOB' => $arFields['WORK_POSITION'],
                        'OLD_JOB' => $arUser['WORK_POSITION'],
                        'UNIT' => array_pop($arFields['UF_DEPARTMENT']),
                        'CHIEF' => $chief,
                    ];
                    $full_name = $arFields['LAST_NAME'] . ' ' . $arFields['NAME'];
                    if ($arUser['PERSONAL_GENDER'] == 'F') {
                        $name = $full_name . ' сменила должность';
                    } else {
                        $name = $full_name . ' сменил должность';
                    }
                    $code = CUtil::translit($name, "ru", $params);
                    $date = new DateTime();

                    $arLoadProductArray = Array(
                        "IBLOCK_SECTION_ID" => false,
                        "IBLOCK_ID" => 35,
                        "PROPERTY_VALUES" => $arProp,
                        "NAME" => $name,
                        "CODE" => $code,
                        "ACTIVE" => "N",
                        "PREVIEW_TEXT" => '<p>' . $full_name . ', желаем удачи на новой должности!</p>',
                        "PREVIEW_TEXT_TYPE" => 'html',
                        'DATE_ACTIVE_FROM' => $date->format('d.m.Y'),
                    );
                    if (!$PRODUCT_ID = $el->Add($arLoadProductArray)) {
                        $arFilter = array("IBLOCK_ID" => 35, "%CODE" => $code);
                        $res = CIBlockElement::GetList(['ID' => 'ASC'], $arFilter, false, false, array("ID", "IBLOCK_ID", "CODE"));
                        while ($arItem = $res->GetNext(true, false)) {
                            $code = $arItem['CODE'];
                        }
                        $arCode = explode('-', $code);
                        $count = (int)$arCode[1] + 1;
                        $code = $arCode[0] . '-' . $count;
                        $arLoadProductArray['CODE'] = $code;
                        if (!$PRODUCT_ID = $el->Add($arLoadProductArray)) {
                            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/.log/change_news-staff_' . date('y-m-d_H-i-s') . '-error.log', var_export(['ERROR' => $el->LAST_ERROR], true));
                        }
                    }
                    if($PRODUCT_ID) CEvent::Send("NEWS_STAFF", "s1", ['ID'=>$PRODUCT_ID,'EVENT'=>'Смена должности','NAME'=>$full_name]);
                }
            }
        }
    }
}