# Manual migration for CE Text/TextPic to TextMedia

This update does the same than Install-Tool Update "ContentTypesToTextMediaUpdate"

@see
https://github.com/TYPO3/TYPO3.CMS/blob/1ce6676d49ca38fe1ca742ea98844747447a1c24/typo3/sysext/install/Classes/Updates/ContentTypesToTextMediaUpdate.php


```mysql
UPDATE tt_content SET CType='textmedia' WHERE tt_content.CType='text';
```

```mysql
UPDATE tt_content
  LEFT JOIN sys_file_reference
  ON sys_file_reference.uid_foreign=tt_content.uid
     AND sys_file_reference.tablenames='tt_content'
     AND sys_file_reference.fieldname='image'
SET
  tt_content.CType='textmedia',
  tt_content.assets=tt_content.image,
  tt_content.image=0, sys_file_reference.fieldname='assets'
WHERE 
  tt_content.CType='textpic'
  OR tt_content.CType='image';
```

```mysql
UPDATE be_groups SET explicit_allowdeny=CONCAT(explicit_allowdeny,',tt_content:CType:textmedia:ALLOW') WHERE (explicit_allowdeny LIKE '%tt\\_content:CType:textpic:ALLOW%' OR explicit_allowdeny LIKE '%tt\\_content:CType:image:ALLOW%' OR explicit_allowdeny LIKE '%tt\\_content:CType:text:ALLOW%') AND explicit_allowdeny NOT LIKE '%tt\\_content:CType:textmedia:ALLOW%';
UPDATE be_groups SET explicit_allowdeny=CONCAT(explicit_allowdeny,',tt_content:CType:textmedia:DENY') WHERE (explicit_allowdeny LIKE '%tt\\_content:CType:textpic:DENY%' OR explicit_allowdeny LIKE '%tt\\_content:CType:image:DENY%' OR explicit_allowdeny LIKE '%tt\\_content:CType:text:DENY%') AND explicit_allowdeny NOT LIKE '%tt\\_content:CType:textmedia:DENY%';
```
