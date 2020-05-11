# FLUX Migration

1. Analyse Template-Usage
   ```mysql
   SELECT CType, COUNT(CType) total_usage, SUM(hidden=1) AS hidden_elements FROM `tt_content`
   WHERE CType LIKE "{ProviderExtensionKey@Lowercase-Without-Dashes}_%" AND not deleted
   GROUP BY CType
   ```
2. Migrate FluidPages to Flux
2.1
   ```mysql
   UPDATE `pages`
     SET backend_layout="flux__grid", backend_layout_next_level="flux__grid"
   WHERE
     backend_layout="fluidpages__fluidpages"
     OR backend_layout_next_level="fluidpages__fluidpages"
     AND NOT deleted="1"
   ```

2.2 Migrate/Refactor Template-Provider Extensions (optional if required)
   ```mysql
   # Backup tx_fed_page_controller_action & tx_fed_page_controller_action_sub
   ALTER TABLE `pages` ADD `zzz_tx_fed_page_controller_action` TEXT NOT NULL;
   UPDATE `pages` SET `zzz_tx_fed_page_controller_action` = `tx_fed_page_controller_action`;

   ALTER TABLE `pages` ADD `zzz_tx_fed_page_controller_action_sub` TEXT NOT NULL;
   UPDATE `pages` SET `zzz_tx_fed_page_controller_action_sub` = `tx_fed_page_controller_action_sub`;
   
   UPDATE `pages`
   SET
      tx_fed_page_controller_action = REPLACE(zzz_tx_fed_page_controller_action, '{ProviderExtensionKey}', '{Vendor}.{ProviderExtensionKeyWithCamelCase}'),
      tx_fed_page_controller_action_sub = REPLACE(zzz_tx_fed_page_controller_action_sub, '{ProviderExtensionKey}', '{Vendor}.{ProviderExtensionKeyWithCamelCase}')
   WHERE `zzz_tx_fed_page_controller_action` != '' OR `zzz_tx_fed_page_controller_action_sub` != ''
   ```

3. Migrate FluidContent to Flux
   ```mysql
   ALTER TABLE `tt_content` ADD `zzz_tx_fed_fcefile` VARCHAR(255) NOT NULL AFTER `tx_fed_fcefile`;
   
   UPDATE `tt_content`
     SET zzz_tx_fed_fcefile=tx_fed_fcefile
   WHERE `tx_fed_fcefile`!="";
   
   UPDATE tt_content 
     SET CType = LOWER(REPLACE(REPLACE(tx_fed_fcefile, '{Vendor}.{ProviderExtensionKeyWithCamelCase}:', 'flux_'), '.html',''))
   WHERE CType = 'fluidcontent_content';
   
   UPDATE `tt_content`
     SET CType=LOWER(
       REPLACE(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ".", 1),
       CONCAT(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ":", 1), ":"),
       CONCAT(REPLACE(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ":", 1), "_", ""), "_")
     )),
    tx_fed_fcefile=REPLACE(zzz_tx_fed_fcefile, "{ProviderExtensionKey}:", "{Vendor}.{ProviderExtensionKeyWithCamelCase}:")
   WHERE `tx_fed_fcefile`!=""
   ```
4. RealURL -> Slug Migration
   
   Slug-Migration may quit with error "missing uid on tx_realurl_pathcache".
   
   ```mysql
   RENAME TABLE `project_sit_herscheid_typo3-v9`.`tx_realurl_pathcache` TO `project_sit_herscheid_typo3-v9`.`zzz_tx_realurl_pathcache`;
   ```
