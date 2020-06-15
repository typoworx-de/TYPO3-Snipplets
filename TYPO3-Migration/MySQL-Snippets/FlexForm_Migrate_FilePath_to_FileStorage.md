 ```mysql
 SET @CType='my_plugin_name';
 SET @fileStorageId=1;
 SET @flexFormXPath='//sheet[@index="general"]/language/field[@index="settings.folder"]/value';

 ALTER TABLE `tt_content` ADD `zzz_pi_flexform` TEXT DEFAULT NULL;
 UPDATE `tt_content` SET zzz_pi_flexform=`pi_flexform`;

 UPDATE tt_content
 SET pi_flexform = UpdateXML(
     pi_flexform,
     @flexFormXPath,
     CONCAT('<value index="vDEF">', @fileStorageId,'</value>')
 )
 WHERE cType = @CType;
 ```
