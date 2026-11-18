# NeoPay Dynamic Retail Laravel

Paquete de Laravel para integrar el POS Android de NeoNet mediante la WebApi **Dynamic Retail v1.2.0**.

Un solo paquete expone las operaciones del POS (ventas, anulaciones, reversas, hoteles, cierres, vouchers y reportes) para que los proyectos no manejen URLs, tokens ni formatos de campos.

## Requisitos

- PHP 8.1 o superior
- Laravel 10, 11 o 12

## Instalación

Cuando el paquete esté publicado:

```bash
composer require softlogic-gt/neopay-dynamic-retail-laravel
```

Mientras esté local, agrega un repositorio `path` en el `composer.json` del proyecto:

```json
"repositories": [
    { "type": "path", "url": "../neopaydynamicretail-laravel" }
]
```

```bash
composer require softlogic-gt/neopay-dynamic-retail-laravel:@dev
```

Si el proyecto corre en Docker, monta la carpeta del paquete como volumen en el `docker-compose.yml` del proyecto y usa esa ruta dentro del contenedor en `url`:

```yaml
volumes:
    - './:/var/www'
    - '../neopaydynamicretail-laravel:/packages/neopaydynamicretail-laravel'
```

```json
{ "type": "path", "url": "/packages/neopaydynamicretail-laravel" }
```

Laravel registra el paquete automáticamente. Para publicar la configuración (opcional):

```bash
php artisan vendor:publish --tag=neopay-dynamic-retail-config
```

## Configuración

Variables del `.env`:

| Variable | Descripción |
|---|---|
| `NEOPAY_POS_URL` | URL base de la API del POS (`.../api/v1/pospayment/`) |
| `NEOPAY_POS_AUTH_URL` | URL del servicio de autenticación (`.../api/Membership/Authenticate`) |
| `NEOPAY_POS_MEMBERSHIP_ID` | `membershipId` entregado por NeoNet |
| `NEOPAY_POS_SECRET_KEY` | `secretKey` entregado por NeoNet |
| `NEOPAY_POS_MERCHANT_USER` | Usuario de autenticación del comercio |
| `NEOPAY_POS_MERCHANT_PASSWORD` | Contraseña de autenticación del comercio |
| `NEOPAY_POS_TERMINAL_ID` | Terminal (8 caracteres) |
| `NEOPAY_POS_CARD_ACQ_ID` | Afiliación (15 caracteres) |
| `NEOPAY_POS_LOG_CHANNEL` | Canal de log de Laravel para registrar peticiones (desactivado si está vacío) |
| `NEOPAY_POS_TIMEOUT` | Timeout en segundos (por defecto 30) |

No se incluyen URLs ni credenciales por defecto: si falta alguna, el paquete lanza `PosException`.

## Uso

Con la fachada `NeoPayPos`:

```php
use SoftlogicGT\NeoPayDynamicRetail\Facades\NeoPayPos;

$response = NeoPayPos::sale(trace: 230, amount: 100.50, tip: 10);

$response->authorization();   // número de autorización
$response->retrievalRefNo();  // número de referencia
$response->clientVoucher();   // comprobante del cliente
$response->commerceVoucher(); // comprobante del comercio
```

O con las acciones directamente:

```php
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;

$response = Authorization::sale(230, 100.50)->submit();
```

Los montos se pasan en decimales (`100.50`); el paquete los convierte al formato de la API (`10050`). El número de auditoría (`trace`) se rellena a 6 dígitos y el folio a 12.

### Operaciones

| Método | Operación |
|---|---|
| `sale($trace, $amount, $tip = null)` | Venta (`SA`). Con `$tip = 0` se envía `000` |
| `salePoints($trace, $points)` | Venta con puntos (`LU`) |
| `saleInstallments($trace, $amount, $installments)` | Venta en cuotas (`VC##`) |
| `saleExtraFinancing($trace, $amount, $plan)` | Venta con extra financiamiento (`EF##`) |
| `saleDiscount($trace, $amount, $discount)` | Venta con descuento (`VD############`) |
| `annulment($trace)` | Anulación (`AN`) |
| `reverse($trace, $amount)` | Reversa (`RV`) |
| `checkIn($trace, $amount, $folio)` | Hotel check-in (`CI`) |
| `surcharge($trace, $amount, $folio)` | Hotel recargo (`RC`) |
| `checkOut($trace, $amount, $folio, $tip = 0, $touristTax = 0)` | Hotel check-out (`CO`) |
| `settlement()` | Cierre de lote |
| `voucherReprint($trace)` | Reimpresión de voucher |
| `sendDigitalVoucher($retrievalRefNo, $type, $to, $clientName)` | Envío de voucher por SMS o correo |
| `transactionDetail()` | Transacciones pendientes de cierre |
| `settlementDetail($batch = null)` | Detalle de un cierre (el último si no se indica) |
| `settlementBatch()` | Listado de cierres |
| `reportTotals()` | Resumen de totales |

### Respuestas y errores

Todas las operaciones devuelven un `PosResponse`. Si la API responde con un `responseCode` distinto de `00`, o falla la conexión o la autenticación, se lanza `PosException`:

```php
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;

try {
    NeoPayPos::sale(230, 100.50);
} catch (PosException $e) {
    $e->getMessage(); // descripción del POS, p. ej. "FONDOS INSUFICIENTES"
    $e->response;     // respuesta completa de la API
}
```

### Timeouts y estado desconocido

Las operaciones son síncronas: la petición espera la respuesta del POS (por ejemplo, a que el cliente pase la tarjeta). Si el POS no está conectado o no responde dentro de `NEOPAY_POS_TIMEOUT`, se lanza `PosConnectionException` (subclase de `PosException`) y la petición **no se reintenta**.

Una `PosConnectionException` significa que el resultado es desconocido: el cobro pudo o no haberse realizado. En ese caso confirma con `transactionDetail()` o usa `reverse()` antes de volver a cobrar.

```php
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosConnectionException;

try {
    NeoPayPos::sale(230, 100.50);
} catch (PosConnectionException $e) {
    // timeout o sin conexión: estado desconocido
} catch (PosException $e) {
    // rechazado por el POS o error HTTP
}
```

### Validación

Antes de enviar cualquier petición se validan los datos y, si algo es inválido, se lanza `InvalidArgumentException` sin llamar a la API:

- Montos, puntos y descuentos mayores que cero y de máximo 12 dígitos (propina e impuesto turístico pueden ser cero).
- Número de auditoría de 1 a 6 dígitos, folio y referencia de 1 a 12 dígitos, solo numéricos.
- Cuotas y plan de extra financiamiento entre 1 y 99.
- En `sendDigitalVoucher`, `$type` y `$to` se envían juntos o ninguno.

### Logging

Desactivado por defecto. Para activarlo, define el canal en el `.env`:

```
NEOPAY_POS_LOG_CHANNEL=stack
```

Se registran la petición, la respuesta y los fallos HTTP de cada llamada. Nunca se registran credenciales ni el token, y se enmascaran el nombre del tarjetahabiente, los vouchers e impresiones de cierre y reportes, el nombre y destino de la notificación, y el número de tarjeta (solo se conservan los últimos 4 dígitos).

### Autenticación

El token se solicita automáticamente, se guarda en caché 4 minutos y se renueva al recibir un 401 (se reintenta la petición una vez). Cada petición envía `Authorization`, `merchantUser` y `merchantPasswd`.

### Pruebas en proyectos que usan el paquete

`PosClient` se resuelve desde el contenedor de Laravel, por lo que puede reemplazarse en las pruebas:

```php
$this->app->instance(PosClient::class, $fakeClient);
```

## Desarrollo

```bash
DOCKER_UID=$(id -u) DOCKER_GID=$(id -g) docker compose up -d --build
docker compose exec app composer install
docker compose exec app composer test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
```

## Licencia

MIT
