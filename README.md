# TODO's List with PHP and HTML5, CSS3 y JavaScript Vanilla

_It's a TODO's app.
Frontend in HTML5, CSS3 and JavaScript Vanilla.
Backend API Rest in PHP OOP with Router._

## Comenzando 🚀

**Clone** this repository to your local machine or **fork** it before **clone** and make your local changes and PRs.

## Requirements 📋

- **PHP** **7.1** or higher
- **MySQL** **5.1** or higher
- **Composer**
- Local Server to serve HTML pages

## Installation 🔧

_This how to assume that you have installed PHP and MySQL in your local machine._


Clone this repo or fork it and clone after:
```
git clone https://github.com/jabaruben/todos-list.git
```
Enter in **back** folder and install composer:
```
cd todos-list
cd back
```
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```
Install all composer dependencies:
```
php composer.phar install
```
Init PHP local development server inside back folder:
```
php -S localhost:8180
```
Next, go to the front folder:
```
cd todos-list
cd front
```
Init a PHP or other local development server here:
```
php -S localhost:8080
```
Next, get from back folder the sqldump.sql file and load into MySQL:
```
mysql -u username -p < sqldump.sql
```
## Construido con 🛠️

* [PHP](https://www.php.net/downloads) - Development Language
* [HTML5](https://es.wikipedia.org/wiki/HTML5)
* [CSS3](https://es.wikipedia.org/wiki/Hoja_de_estilos_en_cascada)
* [JavaScript](https://es.wikipedia.org/wiki/Hoja_de_estilos_en_cascada)
* [MySQL](https://www.mysql.com/)
* [Composer](https://getcomposer.org/)

## Autor ✒️

* **Rubén Carracedo** - *All Project* - [jabaruben](https://github.com/jabaruben)

## License 📄

This project is under MIT License - see [LICENSE.md](LICENSE.md) for details.

## Acknowledgement 🎁

* Comment with others about this 📢
* Invite me a beer 🍺 or a coffee ☕
* Send greetings 🤓.

---
⌨️ with ❤️ by [Jabaruben](https://github.com/jabaruben) 😊