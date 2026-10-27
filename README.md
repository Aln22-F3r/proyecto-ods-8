## Instalación

1. composer install
2. npm install
3. Copiar .env.example a .env, configurar la base de datos y definir ADMIN_SEED_PASSWORD
4. php artisan key:generate
5. php artisan migrate
6. php artisan db:seed
7. php artisan db:seed --class=AdministradorSeeder
8. php artisan storage:link
9. npm run dev y php artisan serve
10. Entrar a /admin/login (usuarios de prueba: admin y lmendez; jruiz está deshabilitado)
