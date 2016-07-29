# Tinyf
Tinyf is a Super Simple PHP Framework based on Composer.
You can extend other package based on this.[This is a case](http://www.kiscms.com/StaticData/application/20160729/579b483a7912e.zip).

```
  ______    _                       _______
 /_  __/   (_)   ____     __  __   /______/
  / /     / /   / __ \   / / / /  / /____        
 / /     / /   / / / /  / /_/ /  / /____/
/_/     /_/   /_/ /_/   \__, /  / /
                       /____/  /_/

```

## Start
### Download:
```bash
git clone https://github.com/buexplain/Tinyf.git Tinyf
cd Tinyf
```
### Install dependencies:

```bash
composer update
```
### Run:
```bash
cd public && php -S 127.0.0.1:8080
```
Visit [http://127.0.0.1:8080/](http://127.0.0.1:8080/)

### It's already running!


## Route examples
*.htaccess config*
```ini
<IfModule mod_rewrite.c>
	 RewriteEngine on
	 RewriteCond %{REQUEST_FILENAME} !-d
	 RewriteCond %{REQUEST_FILENAME} !-f
	 RewriteRule ^.*$ index.php [L]
</IfModule>
```

*nginx config*
```ini
location / {
    try_files $uri $uri/ /index.php?/$uri;
}
```

*config/routes.php :*

```php
use Tinyf\Route as route;

route::get('','Home\Welcome@index');

route::get('/user/{id}','Home\Welcome@index')->where('id','[\d]+')->name('userInfo');

route::get('/{id?}','Home\Welcome@index');

route::any('foo', function() {
    echo "Foo!";
});
```
## Template syntax examples

```html
@include('Home.header');

@if(!isset($id))
    no id
@elseif(!empty($id))
    id:{{$id}}
@else
    id is empty
@endif

@foreach($arr as $k=>$v)
    {{$k}}--->{{$v}}
@endforeach

@for($i=0;$i<5;$i++)
    $i-->{{$i}}
@endfor

@while ($i <= 10)
    $i-->{{$i}}
@endwhile
```

## License

The Tinyf framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)