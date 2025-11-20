# SuiteCRM PowerPack API Guide

This guide covers the SuiteCRM v8 REST API setup and usage with the PowerPack Docker image.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [OAuth2 Setup](#oauth2-setup)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
- [Common Issues](#common-issues)

## 🚀 Quick Start

The PowerPack image is pre-configured with automated OAuth2 key generation during installation.

**Access your API at:** `http://your-domain:8080/legacy/Api/V8/`

## 🔐 OAuth2 Setup

### Automatic Key Generation (PowerPack Feature)

The silent installation automatically generates OAuth2 keys in the correct location. No manual steps needed!

**Keys are generated at:** `/bitnami/suitecrm/public/legacy/Api/V8/OAuth2/`

### Manual Key Generation (If Needed)

If you need to regenerate keys:

```bash
# Access container
docker exec -it suitecrm bash

# Navigate to OAuth2 directory
cd /bitnami/suitecrm/public/legacy/Api/V8/OAuth2

# Generate private key (2048-bit RSA)
openssl genrsa -out private.key 2048

# Generate public key
openssl rsa -in private.key -pubout -out public.key

# Set correct ownership (Bitnami uses daemon user)
chown daemon:daemon private.key public.key

# Set secure permissions
chmod 600 private.key
chmod 644 public.key
```

### Create API Client Credentials

1. Log into SuiteCRM at `http://your-domain:8080`
2. Navigate to **Admin → OAuth2 Clients and Tokens**
3. Click **Create OAuth2 Clients**
4. Fill in the form:
   - **Name**: Your application name (e.g., "Mobile App")
   - **Secret**: Auto-generated (copy this!)
   - **Is Confidential**: Yes (for secure clients)
   - **Allowed Grant Type**: Choose appropriate grant type
5. Save the record
6. Copy the **ID** (this is your `client_id`) and **Secret** (this is your `client_secret`)

## 🔑 Authentication

### Grant Types

SuiteCRM supports two main OAuth2 grant types:

#### 1. Client Credentials Grant (Server-to-Server)

Use for machine-to-machine communication. Does not return a refresh token.

```bash
curl -X POST 'http://localhost:8080/legacy/Api/access_token' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "grant_type": "client_credentials",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET"
  }'
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

#### 2. Password Grant (User Context)

Use to act on behalf of a specific user. Returns a refresh token.

```bash
curl -X POST 'http://localhost:8080/legacy/Api/access_token' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "grant_type": "password",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "username": "admin",
    "password": "your_password"
  }'
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def5020089a5e..."
}
```

#### 3. Refresh Token Grant

Use to get a new access token without re-entering credentials.

```bash
curl -X POST 'http://localhost:8080/legacy/Api/access_token' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "grant_type": "refresh_token",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "refresh_token": "YOUR_REFRESH_TOKEN"
  }'
```

## 📡 API Endpoints

All requests must include the `Authorization: Bearer <token>` header and use `application/vnd.api+json` content type.

### Leads Management

#### Create a Lead

```bash
curl -X POST 'http://localhost:8080/legacy/Api/V8/module' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json' \
  --data-raw '{
    "data": {
      "type": "Leads",
      "attributes": {
        "first_name": "John",
        "last_name": "Doe",
        "email1": "john.doe@example.com",
        "phone_work": "+1-555-555-5555",
        "status": "New",
        "lead_source": "Web Site"
      }
    }
  }'
```

#### Get All Leads

```bash
curl -X GET 'http://localhost:8080/legacy/Api/V8/module/Leads' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

#### Get Single Lead by ID

```bash
curl -X GET 'http://localhost:8080/legacy/Api/V8/module/Leads/{lead_id}' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

#### Filter Leads by Phone Number

```bash
curl -X GET 'http://localhost:8080/legacy/Api/V8/module/Leads?filter[phone_work][eq]=%2B1-555-555-5555' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

**Filter Operators:**
- `eq` - Equals
- `neq` - Not equals
- `gt` - Greater than
- `gte` - Greater than or equal
- `lt` - Less than
- `lte` - Less than or equal
- `like` - Contains

#### Update a Lead

```bash
curl -X PATCH 'http://localhost:8080/legacy/Api/V8/module' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json' \
  --data-raw '{
    "data": {
      "type": "Leads",
      "id": "{lead_id}",
      "attributes": {
        "status": "Contacted",
        "description": "Updated after initial contact."
      }
    }
  }'
```

#### Delete a Lead

```bash
curl -X DELETE 'http://localhost:8080/legacy/Api/V8/module/Leads/{lead_id}' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

### Contacts Management

#### Create a Contact

```bash
curl -X POST 'http://localhost:8080/legacy/Api/V8/module' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json' \
  --data-raw '{
    "data": {
      "type": "Contacts",
      "attributes": {
        "first_name": "Jane",
        "last_name": "Smith",
        "email1": "jane.smith@example.com",
        "phone_work": "+1-555-555-1234"
      }
    }
  }'
```

#### Get All Contacts

```bash
curl -X GET 'http://localhost:8080/legacy/Api/V8/module/Contacts' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

### Accounts Management

#### Create an Account

```bash
curl -X POST 'http://localhost:8080/legacy/Api/V8/module' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json' \
  --data-raw '{
    "data": {
      "type": "Accounts",
      "attributes": {
        "name": "Acme Corporation",
        "phone_office": "+1-555-123-4567",
        "website": "https://acme.com",
        "industry": "Technology"
      }
    }
  }'
```

### PowerPack Custom Modules

#### Lead Journey Timeline

```bash
# Get journey events for a lead
curl -X GET 'http://localhost:8080/legacy/Api/V8/module/LeadJourney?filter[parent_id][eq]={lead_id}&filter[parent_type][eq]=Leads' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json'
```

#### Create Journey Event

```bash
curl -X POST 'http://localhost:8080/legacy/Api/V8/module' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>' \
  --header 'Content-Type: application/vnd.api+json' \
  --data-raw '{
    "data": {
      "type": "LeadJourney",
      "attributes": {
        "name": "Email Opened",
        "parent_type": "Leads",
        "parent_id": "{lead_id}",
        "touchpoint_type": "email",
        "touchpoint_date": "2025-11-21 10:30:00",
        "source": "Campaign ABC"
      }
    }
  }'
```

## 🔧 Common Issues

### 1. "Invalid key supplied" Error

**Symptom:** 500 error with `LogicException: Invalid key supplied`

**Cause:** OAuth2 keys are missing or have wrong permissions

**Solution:**
```bash
# Regenerate keys (see OAuth2 Setup section above)
# Or restart container to trigger auto-generation
docker restart suitecrm
```

### 2. "OAuth2Clients module with id X is not found"

**Symptom:** 401 error when authenticating

**Cause:** Using invalid client credentials or client doesn't exist

**Solution:**
- Create OAuth2 client in SuiteCRM Admin
- Verify you're using the correct `client_id` and `client_secret`
- Check that the client record is not marked as deleted

### 3. "Invalid grant" Error

**Symptom:** Error when using password grant

**Cause:** Incorrect username/password or grant type not allowed

**Solution:**
- Verify SuiteCRM username and password are correct
- Check OAuth2 client has "password" grant type enabled
- Ensure user account is active

### 4. 404 Not Found

**Symptom:** API endpoint returns 404

**Cause:** Incorrect API path (missing `/legacy/` prefix)

**Solution:**
- Always use `/legacy/Api/V8/` prefix for v8 API
- Example: `http://localhost:8080/legacy/Api/V8/module/Leads`

### 5. CORS Errors (Browser/Web Apps)

**Symptom:** Browser blocks API requests due to CORS policy

**Solution:**
Add CORS headers to SuiteCRM or use a reverse proxy:
```nginx
add_header 'Access-Control-Allow-Origin' '*';
add_header 'Access-Control-Allow-Methods' 'GET, POST, PATCH, DELETE, OPTIONS';
add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type';
```

## 📚 Additional Resources

- [SuiteCRM v8 API Documentation](https://docs.suitecrm.com/8.x/developer/api/)
- [JSON:API Specification](https://jsonapi.org/)
- [OAuth2 RFC 6749](https://tools.ietf.org/html/rfc6749)

## 💡 Best Practices

1. **Security:**
   - Never commit API credentials to version control
   - Use environment variables for sensitive data
   - Implement token refresh logic for long-running applications
   - Use HTTPS in production

2. **Performance:**
   - Cache access tokens until they expire
   - Use pagination for large result sets
   - Filter and sort on the server side

3. **Error Handling:**
   - Check HTTP status codes
   - Parse error messages from response body
   - Implement exponential backoff for rate limits

4. **Token Management:**
   - Store refresh tokens securely
   - Implement automatic token refresh
   - Handle token expiration gracefully

## 🆘 Support

For issues specific to the PowerPack features (Twilio Integration, Lead Journey, Funnel Dashboard), please refer to the main [README.md](README.md) or open an issue on GitHub.
