<?php

class_alias('PublishPress\\WordpressVersionNotices\\Autoloader', 'PPVersionNotices\\Autoloader');

class_alias(
    'PublishPress\\WordpressVersionNotices\\Module\\TopNotice\\Module',
    'PPVersionNotices\\Module\\TopNotice\\Module'
);

class_alias(
    'PublishPress\\WordpressVersionNotices\\Module\\MenuLink\\Module',
    'PPVersionNotices\\Module\\MenuLink\\Module'
);

class_alias('PublishPress\\WordpressVersionNotices\\Module\\AdInterface', 'PPVersionNotices\\Module\\AdInterface');

class_alias(
    'PublishPress\\WordpressVersionNotices\\Template\\TemplateLoader',
    'PPVersionNotices\\Template\\TemplateLoader'
);

class_alias(
    'PublishPress\\WordpressVersionNotices\\Template\\TemplateInvalidArgumentsException',
    'PPVersionNotices\\Template\\TemplateInvalidArgumentsException'
);

class_alias(
    'PublishPress\\WordpressVersionNotices\\Template\\TemplateLoaderInterface',
    'PPVersionNotices\\Template\\TemplateLoaderInterface'
);

class_alias(
    'PublishPress\\WordpressVersionNotices\\Template\\TemplateNotFoundException',
    'PPVersionNotices\\Template\\TemplateNotFoundException'
);
