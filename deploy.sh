composer install --no-dev
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

php artisan config:clear
php artisan key:generate
php artisan storage:link
php artisan migrate

php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=ItemSeeder