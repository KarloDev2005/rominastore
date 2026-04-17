import requests
import json

# --- CONFIGURACIÓN ---
PHONE_NUMBER_ID = "1129069736946295"
ACCESS_TOKEN = "EAAecS3E8AysBRLzY4l9ICOWX3cbd10ktRPMgt2ja1z1oGviVFOYFgr8Szjnr0uQ9nIQTxUjR0jmjskIMn63wZB4xWM6eZAaTXk9nF2I5s4yCy5Rk8ZCqgkY9VRZADR29YoIwsZAhykr3o5Ka2aBVZBgbMfZC89HPiLwOJjZA2BE26A9RcKzzpSJ4nI1LWt98FMHZBjU0IdeeF2FEduOjcClfLzrLTJ9UcwZAffLMENIrqEA5IURvSRUXyniaJzTI3sh8fY1HxDsSf95VHkZB7YlcoDf" # ¡OJO! Pega el token nuevo que generes hoy
NUMERO_DESTINO = "522871246175" # Usa el formato exacto que Meta reconoce (con el 1 después del 52)

# --- NUEVO PAYLOAD (Tipo Plantilla) ---
url = f"https://graph.facebook.com/v25.0/{PHONE_NUMBER_ID}/messages"
headers = {
    "Authorization": f"Bearer {ACCESS_TOKEN}",
    "Content-Type": "application/json"
}

# Este es el formato que Meta exige ahora para la Sandbox
payload = {
    "messaging_product": "whatsapp",
    "to": NUMERO_DESTINO,
    "type": "template",
    "template": {
        "name": "hello_world",
        "language": {
            "code": "en_US"  # O "es_ES" si la creaste en español
        }
    }
}

try:
    respuesta = requests.post(url, headers=headers, json=payload)
    respuesta.raise_for_status()
    print("✅ ¡Mensaje de PLANTILLA enviado con éxito!")
    print(json.dumps(respuesta.json(), indent=2))
except requests.exceptions.RequestException as e:
    print(f"❌ Error: {e}")
    if respuesta.text:
        print(respuesta.text)