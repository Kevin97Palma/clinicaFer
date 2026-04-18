#!/usr/bin/env python3
"""
SGC — Ejecuta schema.sql + seed.sql en el servidor remoto.
Uso: python database/run_setup.py
"""
import pymysql
import os
import re
import sys

DB_HOST = '31.97.102.179'
DB_USER = 'root'
DB_PASS = 'Soporte@Palm4'
DB_NAME = 'sgc_clinica'

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

def banner(msg):
    print(f"\n{'='*60}")
    print(f"  {msg}")
    print('='*60)

def limpiar_stmt(raw_stmt):
    """Elimina líneas de comentario y retorna el SQL ejecutable."""
    lines = raw_stmt.splitlines()
    sql_lines = [l for l in lines if not l.strip().startswith('--')]
    return '\n'.join(sql_lines).strip()

def ejecutar_sql(conn, filepath, label):
    print(f"\n--- {label} ---")
    with open(filepath, 'r', encoding='utf-8-sig') as f:
        raw = f.read()

    # Split por ; y limpiar comentarios de cada chunk
    chunks = raw.split(';')
    ok = err = skip = 0
    with conn.cursor() as cur:
        for chunk in chunks:
            stmt = limpiar_stmt(chunk)
            if not stmt:
                skip += 1
                continue
            try:
                cur.execute(stmt)
                ok += 1
            except pymysql.err.IntegrityError as e:
                if 'Duplicate entry' in str(e):
                    skip += 1
                else:
                    print(f"  [WARN] IntegrityError: {e}")
                    err += 1
            except pymysql.err.ProgrammingError as e:
                print(f"  [WARN] SQL error en: {stmt[:80].replace(chr(10),' ')}...")
                print(f"         {e}")
                err += 1
            except Exception as e:
                print(f"  [WARN] {e}")
                err += 1
    conn.commit()
    print(f"  [OK] Ejecutados: {ok}  |  Saltados/vacíos: {skip}  |  Errores: {err}")

banner("SGC — Setup de Base de Datos")

# 1. Conectar sin BD
print("\n[1] Conectando al servidor MySQL...")
try:
    conn = pymysql.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS,
        charset='utf8mb4', autocommit=False, connect_timeout=15
    )
    print(f"    Servidor: MySQL {conn.get_server_info()}")
except Exception as e:
    print(f"[ERROR] {e}")
    sys.exit(1)

# 2. Crear base de datos
print(f"\n[2] Creando base de datos '{DB_NAME}'...")
with conn.cursor() as cur:
    cur.execute(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
    cur.execute(f"USE `{DB_NAME}`")
conn.commit()
conn.select_db(DB_NAME)
print(f"    [OK] Base de datos '{DB_NAME}' lista.")

# 3. Schema
ejecutar_sql(conn, os.path.join(SCRIPT_DIR, 'schema.sql'), 'Ejecutando schema.sql')

# 4. Seed
ejecutar_sql(conn, os.path.join(SCRIPT_DIR, 'seed.sql'), 'Ejecutando seed.sql')

# 5. Actualizar contraseñas con bcrypt real
print("\n[5] Generando contraseñas reales con bcrypt...")
try:
    import bcrypt
    password_admin = 'Admin1234!'.encode('utf-8')
    hash_admin = bcrypt.hashpw(password_admin, bcrypt.gensalt()).decode('utf-8')
    method = 'bcrypt'
except ImportError:
    # Fallback: SHA256 doble (usar bcrypt en producción)
    import hashlib
    hash_admin = hashlib.sha256(b'Admin1234!').hexdigest()
    method = 'sha256-fallback (instalar: pip install bcrypt)'

with conn.cursor() as cur:
    cur.execute("UPDATE usuarios SET password_hash = %s", (hash_admin,))
    # Representantes: su cédula como contraseña
    cur.execute("SELECT u.id, u.cedula FROM usuarios u JOIN usuario_clinica uc ON uc.usuario_id = u.id WHERE uc.rol = 'representante'")
    reps = cur.fetchall()
    for uid, cedula in reps:
        try:
            import bcrypt
            h = bcrypt.hashpw(cedula.encode(), bcrypt.gensalt()).decode()
        except ImportError:
            import hashlib
            h = hashlib.sha256(cedula.encode()).hexdigest()
        cur.execute("UPDATE usuarios SET password_hash = %s WHERE id = %s", (h, uid))
conn.commit()
print(f"    [OK] Método: {method}")

# 6. Verificación
print("\n[6] Verificando tablas creadas...")
with conn.cursor() as cur:
    cur.execute("SHOW TABLES")
    tables = [row[0] for row in cur.fetchall()]
print(f"    Tablas: {', '.join(tables)}")

with conn.cursor() as cur:
    cur.execute("SELECT COUNT(*) FROM usuarios")
    u = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM pacientes")
    p = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM citas")
    c = cur.fetchone()[0]
print(f"    Usuarios: {u}  |  Pacientes: {p}  |  Citas: {c}")

conn.close()

banner("SETUP COMPLETADO EXITOSAMENTE")
print("""
Credenciales de prueba (contraseña: Admin1234!):
  superadmin  → kevin@socket-studio.ec
  gerente     → ana.torres@catpi-sas.com
  supervisor  → carlos.vasquez@catpi-sas.com
  operativo   → maria.gutierrez@catpi-sas.com
  operativo   → luis.medina@catpi-sas.com
  represent.  → roberto.alvarado@gmail.com  (pass: cédula 0923456789)
  represent.  → patricia.molina@gmail.com   (pass: cédula 0934567890)
""")
