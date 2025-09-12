# .htaccess Snippets

# Stage Protection
```
### Stage Protection ###
SetEnvIfNoCase Host "^(test01|stage)\.www\.foobar\.com$" environment=staging
SetEnvIfNoCase Host "^www\.foobar\.com$" environment=production
SetEnvIfNoCase Host "^.+\.(ddev\.site|docker)$" environment=development

AuthType Basic
AuthName "Staging"
AuthUserFile /var/www/vhosts/stoebich.com/./stage-htpasswd

<RequireAny>
    Require expr "%{ENV:environment} == 'production'"
    Require expr "%{ENV:environment} == 'development'"
    <RequireAll>
        Require expr "%{ENV:environment} == 'staging'"
        Require valid-user
    </RequireAll>
    Require expr "-z %{ENV:environment}"
</RequireAny>
```
