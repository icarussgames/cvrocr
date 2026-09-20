# valoriza_obras en PHP

Portación del motor Python (`motor_calculo/`, `valoriza_app/valorizacion.py`)
a PHP puro (sin frameworks, sin `composer install`) — esta es la versión
definitiva del proyecto, con las 10 tipologías ya calculando correctamente.

## Estructura de archivos

```
piloto_php/
├── cargar.php                     ← punto de entrada único de toda la lógica
├── funciones/
│   ├── constantes.php             (física y negocio: GRAVEDAD, UF_CLP, etc.)
│   ├── hidraulica.php             (Manning, altura crítica)
│   ├── parametros.php             (cuantía de acero, espesores, etc.)
│   ├── comun.php                  (excedentes() - compartida entre cubicaciones)
│   ├── tablas.php                 (selección en tablas estándar, cajones)
│   ├── valorizacion.php           (precios.csv, listar_tipologias, valorizar())
│   └── cubicaciones/
│       ├── revestimiento_canal.php
│       ├── disipador.php
│       ├── desarenador.php
│       ├── partidor.php
│       ├── compuerta.php
│       ├── cajon.php
│       ├── canoa.php
│       ├── reparacion_sifon.php
│       ├── reja_sifon.php
│       └── tunel_shotcrete.php
├── api-tipologias.php
├── api-vectores-precio.php
├── api-valorizar.php
├── test_piloto.php
├── index.html / vue.global.js / images/
└── precios.csv
```

**Cada archivo de `cubicaciones/` es autocontenido**: tiene la fórmula
de cálculo (`cubicacion_X()`), la función que arma las partidas del
presupuesto (`_X($p)`), y su registro completo en `$TIPOLOGIAS`
(título, campos del formulario, qué función usar). Para corregir o
ajustar una tipología puntual, solo se toca su archivo — no hay riesgo
de afectar a las otras 9 por error.

Para **agregar una tipología nueva**: crear su archivo en
`funciones/cubicaciones/nombre.php` siguiendo el mismo patrón, y
aparece sola (se carga automáticamente vía `glob()` en `cargar.php`,
sin tocar ningún otro archivo).

Todos los `api-*.php` y `test_piloto.php` cargan la lógica con:
```php
require __DIR__ . '/cargar.php';
```

## Por qué no viene ya probado

Este proyecto se armó en un entorno sin PHP instalado y sin acceso a
internet para instalarlo, así que **no pude ejecutarlo yo mismo**. Lo que
sí hice fue calcular el resultado esperado con la versión Python real
(que sí pude correr) para cada caso — están en los archivos
`esperado_*.json`. Tu trabajo al probar es confirmar que el PHP da
exactamente esos números.


## Cómo probarlo

### Opción A - en tu computador, si tienes PHP instalado
```bash
php -S localhost:8000
```
y abre `http://localhost:8000` en el navegador.

### Opción B - subiéndolo a tu hosting (Webuzo/cPanel)
1. Sube toda esta carpeta a `public_html/` (o una subcarpeta, ej.
   `public_html/piloto/` - ya dejé las rutas relativas para que funcione
   en cualquiera de los dos casos).
2. Abre `tudominio.cl/test_piloto.php` (o `tudominio.cl/piloto/test_piloto.php`
   si la subiste a una subcarpeta) - debería imprimir un JSON.
3. Compáralo campo por campo contra `esperado_python.json` (mismo
   contenido, calculado en Python).
4. Si coinciden, abre `index.html` en esa misma carpeta - debería
   funcionar la app completa, con las 10 tipologías disponibles en el
   dropdown.

## Qué comparar puntualmente

Los valores clave del caso de prueba (caudal 0.67, largo 100, base 1,
altura 1, talud 0):

- Altura de cálculo (Manning): **0.829 m**
- Espesor de revestimiento: **0.13 m**
- Costo directo: **$30.683.973**
- TOTAL CON IVA: **$45.277.270** (**1.136,43 UF**)

## Diferencia a tener en cuenta: redondeo

Python (`round()`) redondea los casos "punto cinco exacto" al par más
cercano (ej. `round(2.5)` da `2`, no `3`) - se llama "banker's rounding".
PHP (`round()`) redondea esos casos siempre hacia arriba, como se enseña
en el colegio (`round(2.5)` da `3`). Para la mayoría de los montos esto
nunca se nota (rarísima vez un monto cae en una fracción exacta de
0.5), pero es una diferencia real entre los dos lenguajes. Si algún día
un total no calza en 1 peso entre ambas versiones, esta es la causa más
probable a revisar primero - no un error de fórmula.

## Estado de las tipologías

**Rediseño en curso** (reunión con el cliente, 26-07-2026): de las 10
tipologías originales van a quedar 8, revisadas una por una. El cliente
definió que la app se enfoca en **cubicación y valorización, no en
diseño hidráulico** - por eso se están quitando cálculos de diseño
(Manning, espesores calculados, etc.) y reemplazándolos por datos que
ingresa directamente el usuario, con notas de verificación simples en
su lugar.

- ✅ **`revestimiento_canal`** - revisada y simplificada según el
  acuerdo con el cliente: ya no calcula altura por Manning ni espesor
  (ambos los ingresa el usuario), cuantía de acero fija en 100 kg/m3
  sobre el hormigón total (canal + muros de ala), roce de faja y
  excavación ampliados (`+5+5` y con profundidad), **incluye muros de
  ala en inicio y término del tramo** (base estabilizada, moldaje y
  hormigón, como partidas separadas - ver "Anexo 9-9 Cubicación",
  sección F, para la fórmula original), y agrega dos notas de
  verificación vía el mecanismo de `notas` (ver más abajo).
  **`esperado_python.json` fue regenerado** para reflejar esta lógica
  nueva - ya no es un espejo de la app Python original, porque la
  lógica ya no es la misma a propósito.
- ⏳ El resto (`disipador`, `desarenador`, `partidor`, `compuerta`,
  `cajon`, `canoa`, `reparacion_sifon`, `reja_sifon`,
  `tunel_shotcrete`) todavía tiene la lógica portada 1:1 desde la app
  Python original - pendientes de revisar una por una y, en varios
  casos, reemplazar por tipologías nuevas.

## Etiqueta personalizada por partida (nuevo)

Cada entrada de `$cantidades` (lo que arma cada `_nombre($p)`) puede
ser `[codigo, cantidad]` o, opcionalmente, `[codigo, cantidad,
etiqueta]`:

```php
['MT003', $c['hormigon_muros_ala'], 'Hormigón muros de ala'],
```

Útil cuando dos partidas de una misma tipología usan el mismo código
de precio (mismo material) pero corresponden a partes distintas de la
obra - sin la etiqueta, ambas se verían con la misma descripción del
CSV. Es retrocompatible (`$entrada[2] ?? null`), no afecta a las
demás tipologías.

## Capítulos y subcapítulos del presupuesto (nuevo)

Cada partida de `$cantidades` puede llevar un 4º elemento opcional con
su "ruta" en la presentación del presupuesto:

```php
['MT027', $c['escarpe'], null, ['Tramo revestido', 'Movimiento de tierras']],
['MT018', $c['moldaje_muros_ala'], 'Moldaje muros de ala', ['Tramo revestido', 'Muros de ala']],
```

La ruta es `['Capítulo']` (sin subcapítulo) o `['Capítulo',
'Subcapítulo']` — así cada tipología define su propia estructura de
capítulos (ninguna otra tipología tiene por qué compartir "Muros de
ala", por ejemplo). `agrupar_por_capitulos()` en
`funciones/valorizacion.php` arma automáticamente:
- La numeración (1, 2.1, 2.1.1...) según orden de aparición.
- El subtotal de cada capítulo (incluye lo que caiga en sus
  subcapítulos) y de cada subcapítulo.

El resultado viaja en `resultado.capitulos` (además de
`resultado.partidas`, que sigue existiendo tal cual, sin agrupar - útil
para el CSV o cualquier vista plana). Es retrocompatible: una partida
sin ruta cae en un capítulo genérico "Partidas".

**Importante: `index.html` todavía no muestra `capitulos`** - hoy
sigue renderizando la tabla plana de `partidas`. Falta decidir cómo se
ve visualmente (tabla con filas de capítulo/subtotal intercaladas,
como tus dos planillas de referencia) y conectarlo.

## Mecanismo de "notas" (nuevo)

Cada `cubicar()` puede devolver un tercer elemento opcional junto a
`partidas` e `informativos`:

```php
return [$partidas, $informativos, $notas];
```

`$notas` es un arreglo de strings libres (a diferencia de
`informativos`, que son tripletas estructuradas nombre/valor/unidad).
`valorizar()` en `funciones/valorizacion.php` lo toma de forma
retrocompatible (`$resultado_cubicar[2] ?? []`), así que las
tipologías que todavía no devuelven notas siguen funcionando igual sin
tocarlas. El campo `notas` ya viaja en el JSON de respuesta de
`/api/valorizar`, pero **`index.html` todavía no lo muestra** - eso
queda pendiente (se mostraría en la columna derecha del presupuesto,
junto a "Notas del cálculo").


## Pendiente ahora que las 10 tipologías están portadas

- Definir con el cliente el formato final del PDF de exportación.
- Revisar si se agregan tipologías nuevas que no estaban en la app
  Python original (mencionado como posible en su momento).
- Cuando existan más columnas de precio en `precios.csv` (vectores por
  zona, etc.), el dropdown "Vector de precios base" las detecta solo -
  no requiere cambios de código.
