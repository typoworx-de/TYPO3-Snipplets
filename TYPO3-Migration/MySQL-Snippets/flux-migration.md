# FLUX Migration

1. Analyse Template-Usage
   ```mysql
   SELECT CType, COUNT(CType) total_usage, SUM(hidden=1) AS hidden_elements FROM `tt_content`
   WHERE CType LIKE "mqlayout_%" AND not deleted
   GROUP BY CType
   ```
2. Migrate FluidPages to Flux
   ```mysql
   UPDATE `pages`
     SET backend_layout="flux__grid", backend_layout_next_level="flux__grid"
   WHERE
     backend_layout="fluidpages__fluidpages"
     OR backend_layout_next_level="fluidpages__fluidpages"
     AND NOT deleted="1"
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
