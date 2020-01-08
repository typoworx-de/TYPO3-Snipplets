
* FluidContent -> Flux Migration

MySQL
```
ALTER TABLE `tt_content` ADD `zzz_tx_fed_fcefile` VARCHAR(255) NOT NULL AFTER `tx_fed_fcefile`;
UPDATE `tt_content`
  SET zzz_tx_fed_fcefile=tx_fed_fcefile
  WHERE `tx_fed_fcefile`!="";

UPDATE tt_content 
  SET CType = LOWER(REPLACE(REPLACE(tx_fed_fcefile, 'Mosaiq.MqLayout:', 'flux_'), '.html', ''))
  WHERE CType = 'fluidcontent_content';

UPDATE `tt_content`
SET CType=LOWER(
    REPLACE(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ".", 1),
    CONCAT(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ":", 1), ":"),
    CONCAT(REPLACE(SUBSTRING_INDEX(zzz_tx_fed_fcefile, ":", 1), "_", ""), "_")
  )),
  tx_fed_fcefile=REPLACE(zzz_tx_fed_fcefile, "mq_layout:", "Mosaiq.MqLayout:")
WHERE `tx_fed_fcefile`!=""
```
