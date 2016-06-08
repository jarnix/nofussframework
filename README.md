No Fuss Framework is a framework designed for PHP 5.6+. It's meant to be fast, easy to learn, and scalable.

It's a professional framework for building small or large projects.

Read the whole documentation on http://www.nofussframework.com.

![logo](http://www.nofussframework.com/assets/img/logo5.png "This logo is terrible")

Updates:
- added build settings (with ant, => lint, phpcbf, etc)
- MIT license

New in 1.3/stable
- Env object (using environment variables and a .env -.ini syntax- at the root of the app)
- Settings object (that merges Env over Config, it's a shortcut)
- Config, Env, and Settings are also "global" objects, and use the same syntax
- + multiple format getters (Settings::get('param.key') or Settings::get()->param->key or $settings = Settings::getInstance && $settings->param->key

New in 1.2.6:
- easier syntax for the url.ini

New in 1.2.3:
- composer support, finally
- moved to a different repo
- created another one for the "empty" website/bootstrap project

New in 1.2.2:
- upsert (insert on duplicate key update obviously)
- easy multithreading (in cli obviously)

New in 1.2.1:
- alternative handling of cli parameters (-name1 value1 -name2 value2)
- "pretty" errors showing an extract of the code
- various bugfixes
- empty site is empty

New in 1.2.0:
- logging db queries to firephp or a file
- upgraded/simplified routing
- middlewares (via routing or programmatically)
- -m (for "make") options for publishing
- compression of all files to a single one
- bugfixes
