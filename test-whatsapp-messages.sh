#!/bin/bash

# WhatsApp Message Types Test Script for Delish ERP
# Tests all supported message formats including catalog orders

API_BASE="http://localhost:8000/api"
WEBHOOK_URL="$API_BASE/webhooks/whatsapp"

# Test merchant phone number
MERCHANT_PHONE="1234567890"

echo "🚀 Testing WhatsApp Message Types for Delish ERP"
echo "================================================"

# Function to send webhook test
send_webhook() {
    local payload="$1"
    local test_name="$2"
    
    echo -e "\n📱 Testing: $test_name"
    echo "-------------------"
    
    response=$(curl -s -X POST "$WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -d "$payload")
    
    echo "Response: $response"
    
    if [[ "$response" == *"success"* ]]; then
        echo "✅ $test_name: PASSED"
    else
        echo "❌ $test_name: FAILED"
    fi
}

# 1. TEXT MESSAGE - Simple Order
echo -e "\n🔤 1. TEXT MESSAGE TESTS"
text_order_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "15551234567",
          "phone_number_id": "123456789"
        },
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_text_001",
          "timestamp": "'$(date +%s)'",
          "text": {
            "body": "Order:\n- Chocolate Cake x2\n- Vanilla Cupcakes x12\n\nDelivery: Tomorrow 2PM\nAddress: 123 Main St"
          },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$text_order_payload" "Text Order Message"

# 2. TEXT MESSAGE - Help Command
help_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_help_001",
          "timestamp": "'$(date +%s)'",
          "text": {
            "body": "help"
          },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$help_payload" "Help Command"

# 3. TEXT MESSAGE - Catalog Command  
catalog_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_catalog_001", 
          "timestamp": "'$(date +%s)'",
          "text": {
            "body": "catalog"
          },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$catalog_payload" "Catalog Browse Command"

# 4. IMAGE MESSAGE with Caption Order
echo -e "\n🖼️ 2. IMAGE MESSAGE TESTS"
image_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_image_001",
          "timestamp": "'$(date +%s)'",
          "type": "image",
          "image": {
            "id": "IMAGE_ID_12345",
            "mime_type": "image/jpeg",
            "caption": "Red Velvet Cake x1\nBirthday decorations\nDelivery: Saturday"
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$image_payload" "Image with Order Caption"

# 5. VIDEO MESSAGE
echo -e "\n🎥 3. VIDEO MESSAGE TESTS"
video_payload='{
  "object": "whatsapp_business_account", 
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_video_001",
          "timestamp": "'$(date +%s)'",
          "type": "video",
          "video": {
            "id": "VIDEO_ID_67890",
            "mime_type": "video/mp4",
            "caption": "Check out this cake design inspiration!"
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$video_payload" "Video Message"

# 6. AUDIO/VOICE MESSAGE
echo -e "\n🔊 4. AUDIO MESSAGE TESTS"
audio_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID", 
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_audio_001",
          "timestamp": "'$(date +%s)'",
          "type": "audio",
          "audio": {
            "id": "AUDIO_ID_11111",
            "mime_type": "audio/ogg; codecs=opus"
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$audio_payload" "Audio/Voice Message"

# 7. DOCUMENT MESSAGE
echo -e "\n📄 5. DOCUMENT MESSAGE TESTS"
document_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_doc_001",
          "timestamp": "'$(date +%s)'",
          "type": "document",
          "document": {
            "id": "DOC_ID_22222",
            "filename": "order_requirements.pdf",
            "mime_type": "application/pdf"
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$document_payload" "Document Message"

# 8. LOCATION MESSAGE  
echo -e "\n📍 6. LOCATION MESSAGE TESTS"
location_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_location_001", 
          "timestamp": "'$(date +%s)'",
          "type": "location",
          "location": {
            "latitude": 37.7749,
            "longitude": -122.4194,
            "name": "Delish Bakery Delivery Location",
            "address": "123 Delivery Street, San Francisco, CA"
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$location_payload" "Location Message"

# 9. INTERACTIVE BUTTON MESSAGE
echo -e "\n🔘 7. INTERACTIVE MESSAGE TESTS"
button_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_button_001",
          "timestamp": "'$(date +%s)'",
          "type": "interactive",
          "interactive": {
            "type": "button_reply",
            "button_reply": {
              "id": "cat_1",
              "title": "🍰 Cakes"
            }
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$button_payload" "Interactive Button Response"

# 10. INTERACTIVE LIST MESSAGE
list_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_list_001",
          "timestamp": "'$(date +%s)'",
          "type": "interactive", 
          "interactive": {
            "type": "list_reply",
            "list_reply": {
              "id": "prod_123",
              "title": "Chocolate Fudge Cake",
              "description": "Rich chocolate cake with fudge frosting"
            }
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$list_payload" "Interactive List Response"

# 11. CATALOG ORDER MESSAGE (The Big One!)
echo -e "\n🛍️ 8. CATALOG ORDER TESTS"
catalog_order_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_catalog_order_001",
          "timestamp": "'$(date +%s)'",
          "type": "order",
          "order": {
            "catalog_id": "CATALOG_12345",
            "text": "Birthday party order - need by Saturday!",
            "product_items": [
              {
                "product_retailer_id": "CAKE_CHOCOLATE_001",
                "quantity": 2,
                "item_price": 45.99,
                "currency": "USD"
              },
              {
                "product_retailer_id": "CUPCAKE_VANILLA_012", 
                "quantity": 24,
                "item_price": 2.50,
                "currency": "USD"
              },
              {
                "product_retailer_id": "COOKIE_SUGAR_025",
                "quantity": 12,
                "item_price": 1.75,
                "currency": "USD"
              }
            ]
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$catalog_order_payload" "Multi-Product Catalog Order"

# 12. COMPLEX MULTI-ITEM CATALOG ORDER
complex_catalog_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp", 
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_complex_catalog_001",
          "timestamp": "'$(date +%s)'",
          "type": "order",
          "order": {
            "catalog_id": "CATALOG_67890",
            "text": "Large wedding order - 200 guests, need consultation on decorations",
            "product_items": [
              {
                "product_retailer_id": "WEDDING_CAKE_TIER3",
                "quantity": 1, 
                "item_price": 299.99,
                "currency": "USD"
              },
              {
                "product_retailer_id": "CUPCAKE_ASSORTED_WEDDING",
                "quantity": 100,
                "item_price": 3.25,
                "currency": "USD"
              },
              {
                "product_retailer_id": "COOKIE_WEDDING_FAVORS", 
                "quantity": 200,
                "item_price": 2.00,
                "currency": "USD"
              },
              {
                "product_retailer_id": "MACARON_TOWER_LARGE",
                "quantity": 2,
                "item_price": 89.99,
                "currency": "USD"
              }
            ]
          }
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$complex_catalog_payload" "Complex Wedding Catalog Order"

# 13. CONTACT MESSAGE
echo -e "\n👤 9. CONTACT MESSAGE TESTS"
contact_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "messages": [{
          "from": "'$MERCHANT_PHONE'",
          "id": "wamid.test_contact_001",
          "timestamp": "'$(date +%s)'",
          "type": "contacts",
          "contacts": [{
            "name": {
              "formatted_name": "Wedding Planner Sarah"
            },
            "phones": [{
              "phone": "+1-555-987-6543",
              "type": "MOBILE"
            }]
          }]
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$contact_payload" "Contact Message"

# 14. MESSAGE STATUS UPDATES
echo -e "\n📊 10. MESSAGE STATUS TESTS"
status_payload='{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "statuses": [{
          "id": "wamid.test_status_001", 
          "status": "delivered",
          "timestamp": "'$(date +%s)'",
          "recipient_id": "'$MERCHANT_PHONE'"
        }]
      },
      "field": "messages"
    }]
  }]
}'

send_webhook "$status_payload" "Message Status Update"

# 15. WEBHOOK VERIFICATION TEST
echo -e "\n✅ 11. WEBHOOK VERIFICATION TEST"
verification_response=$(curl -s -X GET "$WEBHOOK_URL?hub.mode=subscribe&hub.verify_token=$(grep WHATSAPP_VERIFY_TOKEN .env | cut -d '=' -f2)&hub.challenge=test_challenge_12345")

echo "Verification Response: $verification_response"
if [[ "$verification_response" == "test_challenge_12345" ]]; then
    echo "✅ Webhook Verification: PASSED"
else
    echo "❌ Webhook Verification: FAILED"
fi

echo -e "\n🎉 WhatsApp Message Type Testing Complete!"
echo "========================================"
echo "📝 Check your Laravel logs for detailed processing information"
echo "📱 All message types should now be handled by the system"
echo -e "\nSupported Message Types:"
echo "✅ Text messages (orders, commands)"
echo "✅ Images with captions" 
echo "✅ Videos and audio files"
echo "✅ Documents and PDFs"
echo "✅ Location sharing"
echo "✅ Interactive buttons and lists"
echo "✅ Contact sharing"
echo "✅ Multi-product catalog orders"
echo "✅ Complex wedding/event orders"
echo "✅ Message delivery status updates"
echo "✅ Webhook verification"