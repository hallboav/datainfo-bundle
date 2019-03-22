## DatainfoBundle

Instalação:

```shell
composer require hallboav/datainfo-bundle
```

Adicionando as rotas:

```yaml
datainfo_bundle_routing:
    resource: "@DatainfoBundle/Resources/config/routing.xml"
```

Parâmetros opcionais:

```yaml
parameters:
    datainfo.client.base_uri: http://sistema.datainfo.inf.br
    datainfo.client.headers.user_agent: launcherApp/1.0-alpha
    datainfo.client.connect_timeout: 30
    datainfo.login.cookie_lifetime: 3600
```
