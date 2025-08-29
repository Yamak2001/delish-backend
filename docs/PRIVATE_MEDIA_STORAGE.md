# Private Media Storage System

## 🔒 Overview

The Delish ERP system now uses **private storage** for all WhatsApp media files (images, videos, audio, documents) with **temporary URL access** for enhanced security and GDPR compliance. This ensures customer media is protected and only accessible to authorized users.

## 🏗️ Architecture

### **Storage Strategy**
```
storage/app/private/whatsapp/
├── images/          # Customer images (cakes, designs, receipts)
├── videos/          # Video messages and design inspiration  
├── audios/          # Voice messages and audio files
├── documents/       # PDFs, order forms, contracts
└── contacts/        # Contact cards and vCards
```

### **Security Model**
- ✅ **Private Storage**: Files stored in `storage/app/private/` (not web-accessible)
- ✅ **Authentication Required**: All access requires valid JWT token
- ✅ **Temporary URLs**: Time-limited signed URLs for file access
- ✅ **Access Logging**: Complete audit trail of file access
- ✅ **GDPR Compliant**: Secure handling of customer data

## 🚀 API Endpoints

### **1. Serve Private Media**
```
GET /api/media/whatsapp/{type}/{filename}
Authorization: Bearer {jwt_token}
```

**Parameters:**
- `type`: `images|videos|audios|documents|contacts`
- `filename`: The media file name

**Response:**
- Returns the file content with appropriate MIME type
- Security headers included
- Access logged for audit

**Example:**
```bash
curl -H "Authorization: Bearer your_jwt_token" \
     "http://localhost:8000/api/media/whatsapp/images/image_2025-08-28_14-30-45_abc12345.jpg"
```

### **2. Generate Temporary URL**
```
POST /api/media/temporary-url
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "media_path": "private/whatsapp/images/filename.jpg",
  "expires_in_hours": 24
}
```

**Response:**
```json
{
  "success": true,
  "temporary_url": "https://domain.com/storage/signed-url...",
  "expires_at": "2025-08-29T14:30:00Z",
  "expires_in_seconds": 86400
}
```

### **3. Get Media File Info**
```
POST /api/media/info
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "media_path": "private/whatsapp/images/filename.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "media_info": {
    "filename": "image_2025-08-28_14-30-45_abc12345.jpg",
    "file_size": 245760,
    "file_size_human": "240 KB",
    "mime_type": "image/jpeg",
    "last_modified": "2025-08-28 14:30:45",
    "path": "private/whatsapp/images/filename.jpg"
  }
}
```

### **4. List WhatsApp Media**
```
GET /api/media/whatsapp?type=images&limit=50
Authorization: Bearer {jwt_token}
```

**Query Parameters:**
- `type` (optional): Filter by media type
- `limit` (optional): Max files to return (default: 50, max: 100)

**Response:**
```json
{
  "success": true,
  "files": [
    {
      "filename": "image_2025-08-28_14-30-45_abc12345.jpg",
      "type": "images",
      "path": "private/whatsapp/images/filename.jpg",
      "size": 245760,
      "size_human": "240 KB",
      "modified_at": "2025-08-28 14:30:45",
      "mime_type": "image/jpeg"
    }
  ],
  "total_count": 1,
  "filtered_by_type": "images"
}
```

## 🔧 WhatsApp Integration

### **Updated Media Download Process**
```php
// WhatsAppMediaService.php - Now uses private storage
public function downloadMedia(string $mediaId, string $messageType = 'unknown'): ?array
{
    // Download from WhatsApp API
    $fileResponse = Http::withToken($this->accessToken)->get($mediaUrl);
    
    // Save to PRIVATE storage (not publicly accessible)
    $relativePath = "private/whatsapp/{$messageType}s/{$filename}";
    Storage::put($relativePath, $fileResponse->body());
    
    return [
        'media_id' => $mediaId,
        'filename' => $filename,
        'path' => $relativePath,
        'private_storage' => true,
        'mime_type' => $mimeType,
        'file_size' => $fileSize
    ];
}
```

### **Accessing Downloaded Media**
```php
// Generate temporary URL for 24 hours
$temporaryUrl = Storage::temporaryUrl(
    'private/whatsapp/images/filename.jpg',
    now()->addHours(24)
);

// Or serve directly through MediaController
$response = app(MediaController::class)->serveWhatsAppMedia(
    $request, 'images', 'filename.jpg'
);
```

## 🛡️ Security Features

### **Authentication & Authorization**
- **JWT Required**: All endpoints require valid authentication token
- **User Validation**: Active user account verification
- **Role-Based Access**: Can be extended with role-based permissions

### **Access Controls**
- **File Path Validation**: Strict validation of file paths and types
- **Extension Filtering**: Only allowed file extensions served
- **Directory Traversal Protection**: Prevents path traversal attacks

### **Security Headers**
```php
'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
'Pragma' => 'no-cache',
'Expires' => '0',
'X-Content-Type-Options' => 'nosniff',
'X-Frame-Options' => 'DENY',
'Content-Security-Policy' => "default-src 'none'",
'Referrer-Policy' => 'no-referrer'
```

### **Audit Logging**
```php
Log::info('WhatsApp media file accessed', [
    'file_path' => $filePath,
    'user_id' => Auth::id(),
    'user_email' => Auth::user()->email,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent()
]);
```

## 📱 Frontend Integration

### **Displaying WhatsApp Media**
```javascript
// Generate temporary URL via API
const response = await fetch('/api/media/temporary-url', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    media_path: 'private/whatsapp/images/filename.jpg',
    expires_in_hours: 2
  })
});

const data = await response.json();
const imageUrl = data.temporary_url;

// Display image with temporary URL
<img src={imageUrl} alt="Customer media" />
```

### **File Upload to Private Storage**
```javascript
// Upload file directly to private storage
const formData = new FormData();
formData.append('file', file);
formData.append('type', 'images');

const response = await fetch('/api/media/upload', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});
```

## 🔧 Configuration

### **Environment Variables**
```env
# Storage Configuration
FILESYSTEM_DISK=local

# Temporary URL Signing (Laravel handles automatically)
APP_KEY=base64:your_app_key_here

# Security Settings
APP_DEBUG=false  # In production
APP_ENV=production  # In production
```

### **Storage Configuration**
```php
// config/filesystems.php - Default configuration works
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],
],
```

## 🧪 Testing

### **Run Complete Test Suite**
```bash
# Test private storage system
./test-private-media-storage.sh

# Test WhatsApp message handling with media
./test-whatsapp-messages.sh
```

### **Manual Testing**
```bash
# Start Laravel server
php artisan serve

# Login and get token
curl -X POST "http://localhost:8000/api/auth/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@delish.com","password":"password123"}'

# Test media access
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost:8000/api/media/whatsapp/images/filename.jpg"

# Generate temporary URL
curl -X POST "http://localhost:8000/api/media/temporary-url" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"media_path":"private/whatsapp/images/filename.jpg"}'
```

## 🚀 Production Deployment

### **Hostinger VPS Configuration**

1. **Storage Permissions**
```bash
# Set proper permissions for private storage
chmod 755 storage/app/private/
chmod 755 storage/app/private/whatsapp/
chmod 644 storage/app/private/whatsapp/*
```

2. **Web Server Security**
```nginx
# Nginx - Block direct access to storage
location ~* ^/storage/app/private/ {
    deny all;
    return 404;
}
```

3. **SSL/TLS Configuration**
```bash
# Ensure HTTPS for all media endpoints
server {
    listen 443 ssl;
    server_name your-domain.com;
    # SSL configuration
}
```

4. **Performance Optimization**
```php
// config/cache.php - Use Redis for better performance
'default' => env('CACHE_STORE', 'redis'),
```

### **Monitoring & Maintenance**

1. **Storage Monitoring**
```bash
# Monitor storage usage
du -sh storage/app/private/whatsapp/
```

2. **Access Log Analysis**
```bash
# Monitor file access patterns
tail -f storage/logs/laravel.log | grep "WhatsApp media file accessed"
```

3. **Automated Cleanup**
```php
// Schedule in app/Console/Kernel.php
$schedule->command('storage:cleanup-old-media')->daily();
```

## 📊 Benefits

### **Security Advantages**
- ✅ **GDPR Compliant**: Customer data properly protected
- ✅ **Access Controlled**: Only authenticated users can access files
- ✅ **Audit Trail**: Complete logging of all file access
- ✅ **Temporary Access**: Time-limited URLs prevent unauthorized sharing

### **Performance Benefits**
- ✅ **Efficient Serving**: Laravel's optimized file serving
- ✅ **Caching Headers**: Proper cache control for better performance
- ✅ **Streaming Response**: Memory-efficient file serving
- ✅ **CDN Ready**: Can be integrated with CDN for global distribution

### **Operational Benefits**
- ✅ **Centralized Management**: All media files in organized structure
- ✅ **Easy Backup**: Clear file organization for backup strategies
- ✅ **Space Efficient**: Automatic file organization and cleanup
- ✅ **Scalable**: Ready for cloud storage integration (S3, etc.)

---

## 🎯 Summary

The private media storage system provides:

1. **🔒 Security**: Private storage with authentication-required access
2. **⏰ Temporary URLs**: Time-limited signed URLs for secure sharing  
3. **📊 Audit Logging**: Complete access tracking for compliance
4. **🚀 Performance**: Optimized file serving with proper headers
5. **📱 Integration**: Seamless WhatsApp media handling
6. **🛡️ GDPR Compliance**: Proper customer data protection

**Your WhatsApp media files are now secure, private, and accessible only to authorized users with full audit trails!** 🎉