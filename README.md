# Valomen.gg – Backend Project

Gestor de contingut, esdeveniments, equips, partits, prediccions i administració amb PHP, sessions, cookies, seguretat i tractament d’imatges.

## Credencials Usuaris
### Administrador
Usuari: **admin**<br>
Contrasenya: **admin**<br>

### Usuari Normal
Usuari: **s.canadas**<br>
Contrasenya: **s.canadas**

## Inicialització de la base de dades
El fitxer `.sql` es troba a la carpeta `config/` (`config/valomen-db.sql`).

---

## 1. Descripció general del projecte

Aquest projecte simula el backend d’un portal relacionat amb eSports, on es poden veure partits, esdeveniments, equips, i on els usuaris poden registrar-se, iniciar sessió i participar en prediccions. El sistema incorpora rols (usuari i administrador), gestió de perfils, panell d’administració, ordenació, paginació, cerca i opcions de seguretat.

A més, com a reflexió del que portem de projecte:

> AQUEST PROJECTE ENS FA ARRIBAR A LA CONCLUSIÓ DEL TEMPS QUE POT ESTALVIAR CONSULTAR INFORMACIÓ DIRECTAMENT D'UNA API, JA QUÈ COM VEIEM, EN AQUEST PROJECTE ES FA TOT A LA ANTIGUA. ES POSA TOT A MÀ. EN CANVI, SI TINGUÉSSIM L'API QUE ENS PROPORCIONA LA INFORMACIÓ, TOT SERIA MOLT MÉS FÀCIL I NO ENS HAURIEM DE PARAR A TENIR MODERADORS QUE VAN AFEGINT PARTITS I EVENTS QUE ES JUGARAN O MODIFICAR EL MARCADOR DELS PARTITS.

També:

> HE VOLGUT IMPLEMENTAR EL TEMA DE LES PREDICCIONS PERQUÈ SI NO NO TINDRIA SENTIT TENIR USUARIS, JA QUE NO PODRIEN FER RES I NO CANVIARIA RES EL TENIR O NO TENIR UN COMPTE, NOMÉS PER A ADMINISTRADORS.  
> VAIG ESTAR PENSANT ENTRE UN SISTEMA DE FOROS O UN SISTEMA DE PREDICCIONS I PUNTS, I EM VAIG DECANTAR PER AQUEST ÚLTIM. VA SER MÉS DIFÍCIL PERQUÈ IMPLICAVA GESTIÓ DE PUNTS, RESULTATS, I ACTUALITZACIONS AUTOMÀTIQUES.

---

## 2. Decisions del projecte segons l’enunciat

### ✔ Arquitectura MVC pròpia (sense frameworks)
- Front controller a `public/index.php`.
- Router propi per registrar rutes GET/POST i executar el controlador corresponent.
- Separació de carpetes: `app/Controller`, `app/Model/DAO`, `app/View`, `config`, `lib`.

### ✔ Autenticació segura
- Sessions PHP per gestionar l’usuari logat.
- Contrasenyes amb `password_hash()` i verificació amb `password_verify()`.
- Validació de duplicats (email, username).

### ✔ OAuth (Google i GitHub)
- Login amb Google i GitHub (callback absolut).
- Sistema d’“identitats” guardat a la taula `oauth_identities`.
- Si l’email ja existeix, s’enllaça el provider amb l’usuari existent.
- Si no existeix, es crea un usuari nou i se li enllaça la identitat OAuth.

### ✔ Recuperació de contrasenya (per email)
- Formulari per demanar l’email i enviar enllaç de reset.
- Token segur tipus `selector:validator` (a la BD només es guarda el hash del validator).
- Caducitat del token (TTL) i ús únic (quan es fa servir, queda invalidat).
- Per seguretat, sempre es mostra “correu enviat” encara que l’email no existeixi.

### ✔ Recordar sessió (Remember Me)
- Implementació amb token.
- Emmagatzemat a BD i cookie amb hash.
- Requerit per seguretat: no es guarda mai la contrasenya.

### ✔ reCAPTCHA després de 3 intents fallits
- Comptador d’intents fallits.
- reCAPTCHA v2 visible.
- Bloca l’inici de sessió si no es supera.

### ✔ Sessió de 40 minuts
- Control de `last_activity`.
- Expiració automàtica i logout segur.

### ✔ Edició del perfil
- Canviar username.
- Canviar avatar.
- Tractament d’imatges amb GD (retallar + redimensionar a 500×500 PNG).
- Avatar per defecte si l’usuari no en té.

### ✔ Rol d'Administrador
- Accés a panell d’administració.
- CRUD complet d’usuaris i equips.
- Eliminació en cascada de prediccions d’un usuari eliminat.
- Edit Mode per modificar contingut al frontend.

### ✔ Ordenació dels articles / esdeveniments
- Per data ASC o DESC.
- Selects combinats amb paginació.

### ✔ Barra de cerca
- Cerca per nom en totes les pàgines.
- Resultats independentment de la paginació.
- Filtrat en PHP.

### ✔ Configuracions de seguretat (.htaccess)
- Reescriptura de rutes cap a `public/index.php` (URL netes).
- Bloqueig d’accés a carpetes internes (`app/`, `config/`, `lib/`) amb `Require all denied`.
- Separació clara entre `/public/` i la resta del backend.

---

## 3. Notes importants d’URLs (portabilitat)
El projecte es mou entre ordinadors i carpetes, per tant:

❌ PROHIBIT
- `href="/profile"`
- `header('Location: /profile')`

✅ OBLIGATORI
- `base_path()`
- `url('profile')`
- `redirect_to('profile')`