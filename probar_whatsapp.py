import requests
import json

# --- CONFIGURACIÓN (Funciona con tu plantilla actual) ---
PHONE_NUMBER_ID = "1129069736946295"
ACCESS_TOKEN = "EAAecS3E8AysBRSdSX8OZCZA8k734dn2sFikZAazSqDC0pt68gvhe8DgTj4W7U5sizpHcCr3NEoFJcmX273234jNDMqPuUvIgfq666Ykf9wK7yIwzrcqWpFXPzWPlnZBtsG9mSGBvY9Wf4ZA4lVMgEEIkFaqGqEO3wqUqfNLvqa1C6fJYyNojpHKp9soKXyz0xbqU5pjvQOZBFslao9SdmpELdnSziyqe5D94393WBlQmWAxfFB8cFngHET9KGXnPZAixLof4bLUw01WQIK8g7FNoQZDZD"
NUMERO_DESTINO = "522871246175"

url = f"https://graph.facebook.com/v25.0/{PHONE_NUMBER_ID}/messages"
headers = {
    "Authorization": f"Bearer {ACCESS_TOKEN}",
    "Content-Type": "application/json"
}

# --- DATOS DE PRUEBA (Cámbialos por los reales cuando quieras) ---
payload = {
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

# --- ENVÍO ---
try:
    respuesta = requests.post(url, headers=headers, json=payload)
    respuesta.raise_for_status()
    print("✅ ¡Notificación enviada con éxito!")
    print(json.dumps(respuesta.json(), indent=2, ensure_ascii=False))
except requests.exceptions.RequestException as e:
    print(f"❌ Error: {e}")
    if respuesta and respuesta.text:
        print("Detalles:", respuesta.text)