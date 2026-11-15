# Laravel code sample — Projects

[![CI](https://github.com/yanmandev/laravel-example/actions/workflows/ci.yml/badge.svg)](https://github.com/yanmandev/laravel-example/actions/workflows/ci.yml)

Пример кода на Laravel 13 / PHP 8.4+: feature-модуль **Projects** из сервиса realtime-уведомлений.
Остальная часть сервиса (сообщения, очереди, Centrifugo, регистрация) сюда не входит — модуль вынесен
в минимальное приложение, которое запускается и тестируется само по себе.

## Предметная область

Пользователь регистрирует **проекты** — внешние приложения, которые будут слать уведомления в сервис.
Каждому проекту выпускаются **API-токены**, которыми приложение аутентифицируется в machine-to-machine запросах.

| Метод | URL | Описание |
|---|---|---|
| `GET` | `/api/projects?perPage=15` | Список своих проектов с пагинацией |
| `POST` | `/api/projects` | Создать проект (не больше N на пользователя) |
| `GET` | `/api/projects/{id}` | Проект по UUID |
| `PUT` | `/api/projects/{id}` | Переименовать (slug пересчитывается) |
| `DELETE` | `/api/projects/{id}` | Удалить вместе с токенами |
| `GET` | `/api/projects/{id}/tokens` | Токены проекта (без секретов) |
| `POST` | `/api/projects/{id}/tokens` | Выпустить токен — значение отдаётся **один раз** |
| `PATCH` | `/api/projects/{id}/tokens/{tokenId}` | Переименовать / (де)активировать |
| `DELETE` | `/api/projects/{id}/tokens/{tokenId}` | Отозвать токен |
| `GET` | `/api/project` | Текущий проект по `Authorization: Bearer <project token>` |

Пользовательские маршруты защищены Sanctum, маршрут `/api/project` — собственным middleware по токену проекта.

## Структура

```
app/Features/Projects/
├── Actions/          # бизнес-сценарии: один класс — одно действие
├── Dto/              # неизменяемые входные/выходные данные actions
├── Exceptions/       # доменные ошибки (рендерятся как 422)
├── Http/
│   ├── Controllers/  # тонкие: HTTP → DTO → action → Resource
│   ├── Middleware/   # аутентификация по токену проекта
│   ├── Requests/     # валидация + авторизация
│   └── Resources/    # формат ответа API
├── Models/
└── Policies/         # доступ только владельцу
```

Модуль организован по фиче, а не по техническим слоям всего приложения: всё, что относится к проектам, лежит рядом.

## На что обратить внимание

- **Тонкие контроллеры, логика в actions.** Actions не знают про HTTP: принимают модель + DTO, поэтому их можно
  вызвать из консольной команды или job. Лимиты берутся из `config/projects.php` через `#[Config]`-инъекцию.
- **Гонки при проверке лимитов.** Проверка «не больше N проектов / токенов» выполняется в транзакции под
  `SELECT … FOR UPDATE` на родительской строке, иначе два параллельных запроса оба прошли бы проверку.
- **Безопасное хранение токенов.** В БД хранится только SHA-256 хэш; открытое значение возвращается один раз при
  создании. Для длинного случайного токена быстрый хэш достаточен и позволяет искать токен по уникальному индексу.
- **Авторизация через policy + scoped bindings.** Чужой проект отдаёт `404`, а не `403`
  (`Response::denyAsNotFound()`), чтобы нельзя было проверить существование UUID. Токен в URL резолвится только
  среди токенов этого проекта (`->scoped()`), так что подставить id чужого токена нельзя — без ручных проверок.
- **Наружу не утекают внутренние id.** Проект в API идентифицируется UUID (`public_id`), автоинкремент остаётся внутри.
- **Меньше записей в БД.** Middleware обновляет `last_used_at` не чаще раза в минуту и без изменения `updated_at`.
- **Единый формат ошибок.** Нарушения бизнес-правил (`ProjectDomainException`) отдаются как 422 в том же виде,
  что и ошибки валидации, — клиенту не нужно различать их.

## Запуск

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

По умолчанию используется SQLite. Токен пользователя для ручной проверки API:

```bash
php artisan tinker --execute="echo App\Models\User::factory()->create()->createToken('dev')->plainTextToken;"
```

## Качество

```bash
composer test      # PHPUnit: feature-тесты на API, авторизацию и middleware
composer analyse   # Larastan, level 8
composer lint      # Laravel Pint + declare(strict_types=1)
```

Всё это же запускается в GitHub Actions на PHP 8.4 и 8.5.
