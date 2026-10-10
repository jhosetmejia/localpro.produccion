#!/usr/bin/env bash
# Crea o repara el usuario administrador de LocalPRO y le pone una clave nueva.
# Uso (como root en la Consola web):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/reset-admin.sh | bash
# Desarrollado por Jhoset Mejía - DiCreWeb
set -euo pipefail
DOMINIO="localpro.narinoexposhow.com"
USUARIO="localpro"
EMAIL="${ADMIN_EMAIL:-jmmg1994@gmail.com}"
RAIZ="/home/$USUARIO/htdocs/$DOMINIO"
cd "$RAIZ"

# Clave sin letras que se confunden (l, I, O, 0, 1)
CLAVE="$(set +o pipefail; head -c 400 /dev/urandom | tr -dc 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789' | head -c 12)"

sudo -u "$USUARIO" php8.2 artisan migrate --force
sudo -u "$USUARIO" env EMAIL="$EMAIL" CLAVE="$CLAVE" php8.2 artisan tinker --execute='
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
if (Permission::count() === 0) {
    foreach (["DocumentoSeeder","ComprobanteSeeder","PermissionSeeder"] as $s) {
        Artisan::call("db:seed", ["--class" => $s, "--force" => true]);
    }
}
$rol = Role::firstOrCreate(["name" => "administrador"]);
$rol->syncPermissions(Permission::all());
$u = App\Models\User::firstOrNew(["email" => getenv("EMAIL")]);
if (!$u->name) { $u->name = "Administrador"; }
$u->password = bcrypt(getenv("CLAVE"));
$u->save();
$u->syncRoles(["administrador"]);
echo "Usuarios en el sistema: " . App\Models\User::pluck("email")->implode(", ") . PHP_EOL;
'
sudo -u "$USUARIO" php8.2 artisan optimize:clear >/dev/null
sudo -u "$USUARIO" php8.2 artisan config:cache >/dev/null

sed -i "s/^ADMIN_PASS=.*/ADMIN_PASS=$CLAVE/" /root/localpro-credenciales.txt 2>/dev/null || true
echo
echo "LISTO. Entre en https://$DOMINIO/login"
echo "Correo: $EMAIL"
echo "Clave:  $CLAVE"
