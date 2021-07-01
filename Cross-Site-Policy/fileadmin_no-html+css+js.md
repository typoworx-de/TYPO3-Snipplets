```
# This file applies Content-Security-Policy (CSP) HTTP headers
# to directories containing (user uploaded) resources like
# /fileadmin/ or /uploads/

<IfModule mod_headers.c>
    # matching requested *.pdf files only (strict rules block Safari showing PDF documents)
    <FilesMatch "\.pdf$">
        Header set Content-Security-Policy "default-src; script-src 'none'; object-src; plugin-types application/pdf;"
    </FilesMatch>
    # matching anything else, using negative lookbehind pattern
    <FilesMatch "(?<!\.pdf)$">
        Header set Content-Security-Policy "default-src; script-src; style-src; object-src 'none'; img-src data:;"
    </FilesMatch>


    # =================================================================
    # Variations to send CSP header only when it has not be set before.
    # Adjust all `Header set` instructions above
    #     Header set Content-Security-Policy "<directives>"
    # with substitutes shown below
    #
    # -----------------------------------------------------------------
    # a) for Apache 2.4 (having `setifempty`)
    # -----------------------------------------------------------------
    #     Header setifempty Content-Security-Policy "<directives>"
    #
    # -----------------------------------------------------------------
    # b) for Apache 2.2 (using fallbacks)
    # -----------------------------------------------------------------
    #     Header append Content-Security-Policy ""
    #     Header edit Content-Security-Policy "^$" "<directives>"
    #
    # =================================================================
</IfModule>
```