# TimeManager Challenge — Symfony + MySQL _ Ing Pablo Miguez

## Requisitos
- PHP >= 8.2 con pdo_mysql habilitado
- Composer
- Symfony CLI
- MySQL 8.x (localhost)

    
## Configuración
```bash
composer install
cp .env .env.local
# Edita DATABASE_URL con tu usuario, clave y puerto
# DATABASE_URL="mysql://root:TU_PASS@127.0.0.1:3306/timemanager?charset=utf8mb4"
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
symfony serve -d
