# flux:field.file

Fix missing images in Backend having seemingly 'incomplete' path in backend

```
<flux:field.file
    name="image"
    label="Image"
    allowed="jpg,jpeg,png"
    uploadFolder="uploads/pics"
++  internalType="file"
++  useFalRelation="false"
    size="1"
    showThumbnails="true"
/>
```
