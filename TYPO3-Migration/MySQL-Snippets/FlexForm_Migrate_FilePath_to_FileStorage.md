```mysql
SET @fileStorageId=1;
SET @filterCType='my_plugin_name';

ALTER TABLE `tt_content` ADD `zzz_pi_flexform` TEXT DEFAULT NULL;
UPDATE `tt_content` SET zzz_pi_flexform=`pi_flexform`;

UPDATE `tt_content`
SET pi_flexform=REPLACE(
  zzz_pi_flexform,
  ExtractValue(zzz_pi_flexform, '//sheet[@index="general"]/language/field[@index="settings.folder"]/value'),
  CONCAT(@fileStorageId,':', ExtractValue(zzz_pi_flexform, '//sheet[@index="general"]/language/field[@index="settings.folder"]/value'))
)
WHERE
 list_type=@filterCType
 AND NOT ExtractValue(zzz_pi_flexform, '//sheet[@index="general"]/language/field[@index="settings.folder"]/value') REGEXP '\d+\:'
```
