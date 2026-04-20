import requests
import json

# --- CONFIGURACIÓN ---
PHONE_NUMBER_ID = "1129069736946295"
ACCESS_TOKEN = "EAAecS3E8AysBRQbZBPwa9Pymgh3TBUX441daPUyImtaWX0cwPCBzPz2lUbZBtW6XNew8vf29ecmrfDFqPVWl0fFhvMMSMzyEConRFPfVZC5RehQAkEYnLY3w2phnEK6PtjclKDe8ZCUKsRV1jnaKD1A8V7eZAepMiEOIHmxwXoclsuE5RkRmRgWgM09Ty7s5jiGZAq303HZCqu5WFYHHpVIzPxjD3caZBlB7bRQfPx33LZCoMVS2uvbaeg6pvHBwiVVyw3aUjsF8uurrqzdoobFVP"
NUMERO_DESTINO = "522871246175"

url = f"https://graph.facebook.com/v25.0/{PHONE_NUMBER_ID}/messages"
headers = {
    "Authorization": f"Bearer {ACCESS_TOKEN}",
    "Content-Type": "application/json"
}

# --- PROBANDO DIFERENTES VARIANTES PARA AISLAR EL ERROR ---

print("=" * 50)
print("🔬 DIAGNÓSTICO DE PLANTILLA")
print("=" * 50)

# 1. Prueba con la plantilla hello_world (debe funcionar, es nuestro control)
print("\n1️⃣ Probando plantilla 'hello_world' (control)...")
payload_hello = {
    "messaging_product": "whatsapp",
    "to": NUMERO_DESTINO,
    "type": "template",
    "template": {
        "name": "hello_world",
        "language": { "code": "en_US" }
    }
}

try:
    r = requests.post(url, headers=headers, json=payload_hello)
    if r.status_code == 200:
        print("   ✅ hello_world FUNCIONA (código 200).")
    else:
        print(f"   ❌ hello_world falló ({r.status_code}): {r.text}")
except Exception as e:
    print(f"   ❌ Error de conexión: {e}")

# 2. Prueba con tu plantilla SIN componentes (texto fijo, por si las variables causan conflicto)
print("\n2️⃣ Probando plantilla 'notificacion_fiado_interno' SIN variables...")
payload_sin_vars = {
    "messaging_product": "whatsapp",
    "to": NUMERO_DESTINO,
    "type": "template",
    "template": {
        "name": "notificacion_fiado_interno",
        "language": { "code": "es_MX" }
        # No enviamos "components"
    }
}

try:
    r = requests.post(url, headers=headers, json=payload_sin_vars)
    if r.status_code == 200:
        print("   ✅ ¡Funcionó sin variables! El problema está en cómo pasas los parámetros.")
    else:
        print(f"   ❌ Falló sin variables ({r.status_code}). Respuesta:")
        print("   " + r.text)
        error_data = r.json().get("error", {})
        if "error_data" in error_data:
            print(f"   📌 Detalle específico: {error_data['error_data']['details']}")
except Exception as e:
    print(f"   ❌ Error: {e}")

# 3. Prueba con variables (igual que antes)
print("\n3️⃣ Probando plantilla CON variables (formato estándar)...")
payload_con_vars = {
    "messaging_product": "whatsapp",
    "to": NUMERO_DESTINO,
    "type": "template",
    "template": {
        "name": "notificacion_fiado_interno",
        "language": { "code": "es_MX" },
        "components": [
            {
                "type": "body",
                "parameters": [
                    {"type": "text", "text": "María López"},
                    {"type": "text", "text": "19/04/2026"},
                    {"type": "text", "text": "Coca-Cola x2 = $40.00"},
                    {"type": "text", "text": "$68.00"},
                    {"type": "text", "text": "$168.00"}
                ]
            }
        ]
    }
}

try:
    r = requests.post(url, headers=headers, json=payload_con_vars)
    if r.status_code == 200:
        print("   ✅ ¡Funcionó con variables! El problema anterior pudo ser temporal.")
    else:
        print(f"   ❌ Falló con variables ({r.status_code}). Respuesta:")
        print("   " + r.text)
        error_data = r.json().get("error", {})
        if "error_data" in error_data:
            print(f"   📌 Detalle específico: {error_data['error_data']['details']}")
except Exception as e:
    print(f"   ❌ Error: {e}")

# 4. Prueba con otro código de idioma (es_LA) por si el problema es el locale
print("\n4️⃣ Probando con código de idioma 'es_LA' (Latinoamérica)...")
payload_es_la = {
    "messaging_product": "whatsapp",
    "to": NUMERO_DESTINO,
    "type": "template",
    "template": {
        "name": "notificacion_fiado_interno",
        "language": { "code": "es_LA" },
        "components": [
            {
                "type": "body",
                "parameters": [
                    {"type": "text", "text": "María López"},
                    {"type": "text", "text": "19/04/2026"},
                    {"type": "text", "text": "Producto prueba"},
                    {"type": "text", "text": "$100.00"},
                    {"type": "text", "text": "$100.00"}
                ]
            }
        ]
    }
}

try:
    r = requests.post(url, headers=headers, json=payload_es_la)
    if r.status_code == 200:
        print("   ✅ ¡Funcionó con 'es_LA'! El código correcto es 'es_LA' en lugar de 'es_MX'.")
    else:
        print(f"   ❌ Falló con 'es_LA' ({r.status_code}). Respuesta:")
        print("   " + r.text)
except Exception as e:
    print(f"   ❌ Error: {e}")

print("\n" + "=" * 50)
print("🏁 Diagnóstico finalizado. Revisa los resultados arriba.")
print("=" * 50)