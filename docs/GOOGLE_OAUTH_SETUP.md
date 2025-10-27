# Google OAuth Setup Guide

This guide explains how to set up Google OAuth credentials for the Perfect Fit mobile app integration.

## Prerequisites

- Google Cloud Console account
- Laravel application with social auth already implemented
- Mobile app (Android/iOS/React Native)

## Complete Setup Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ Step 1: Google Cloud Console Setup                              │
├─────────────────────────────────────────────────────────────────┤
│ 1. Create Project → 2. Enable APIs → 3. Create OAuth Credentials │
│ 4. Configure Consent Screen → 5. Set Authorized Origins/Redirects │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ Step 2: Laravel Configuration                                   │
├─────────────────────────────────────────────────────────────────┤
│ 1. Update .env with OAuth credentials → 2. Configure Services    │
│ 3. Clear Config Cache → 4. Test Token Verification              │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ Step 3: Mobile App Integration                                  │
├─────────────────────────────────────────────────────────────────┤
│ 1. Install Google SDK → 2. Configure Client ID → 3. Implement UI │
│ 4. Handle Authentication → 5. Send Token to API → 6. Store Token │
└─────────────────────────────────────────────────────────────────┘
```

## Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" → "New Project"
3. Enter project name (e.g., "Perfect Fit Mobile")
4. Click "Create"

## Step 2: Enable Required APIs

1. Go to "APIs & Services" → "Library"
2. Search for and enable:
   - Google+ API (or Google People API)
   - If using Firebase: Firebase Authentication API

## Step 3: Create OAuth 2.0 Credentials

### For Web Application (Server-side verification)

1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "OAuth 2.0 Client IDs"
3. Choose "Web application"
4. Fill in the details:
   - **Name**: Perfect Fit API
   - **Authorized JavaScript origins**: `http://localhost:8000` (for development)
   - **Authorized redirect URIs**: `http://localhost:8000/auth/google/callback`
5. Click "Create"
6. Copy the Client ID and Client Secret

### For Mobile Applications

1. Create separate credentials for your mobile app
2. Choose "Android" or "iOS" application type
3. For Android:
   - Package name: `com.perfectfit.app`
   - SHA-1 certificate fingerprint: Get from your Android Studio
4. For iOS:
   - Bundle ID: `com.perfectfit.app`
5. Copy the Client ID (no secret needed for mobile)

## Step 4: Configure OAuth Consent Screen

1. Go to "OAuth consent screen"
2. Choose "External" (or "Internal" if only your organization)
3. Fill in required fields:
   - App name: Perfect Fit
   - User support email: your-email@example.com
   - Developer contact information: your-email@example.com
4. Add scopes:
   - `profile` (for basic profile info)
   - `email` (for email address)
5. Add test users if in "Testing" mode

## Step 5: Update Laravel Configuration

Update your `.env` file with the credentials:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID="your_web_client_id_here"
GOOGLE_CLIENT_SECRET="your_web_client_secret_here"
GOOGLE_REDIRECT_URL="http://localhost:8000/auth/google/callback"
```

For production, update the redirect URL to your production domain.

## Step 6: Mobile App Configuration

### Android

In your `google-services.json` or code:

```kotlin
GoogleSignin.configure {
    webClientId = "your_web_client_id_here" // From Google Cloud Console
}
```

### iOS

In your `GoogleService-Info.plist` or code:

```swift
GIDSignIn.sharedInstance.configuration = GIDConfiguration(clientID: "your_web_client_id_here")
```

### React Native

```javascript
GoogleSignin.configure({
  webClientId: 'your_web_client_id_here',
  offlineAccess: false,
});
```

## Step 7: Test the Integration

### Test API Endpoint

```bash
curl -X POST http://localhost:8000/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{
    "token": "your_google_id_token",
    "device_id": "test_device_123"
  }'
```

### Get Google ID Token for Testing

1. Go to [OAuth 2.0 Playground](https://developers.google.com/oauthplayground)
2. Select "Google+ API v1" → "https://www.googleapis.com/auth/userinfo.profile"
3. Click "Authorize APIs" and sign in with your Google account
4. Click "Exchange authorization code for tokens"
5. Copy the "Id token" value
6. Use this token in your API requests

## Step 8: Production Deployment

1. Update authorized origins and redirect URIs for production domain
2. Switch OAuth consent from "Testing" to "In production"
3. Add production app package names/bundle IDs
4. Update mobile app configurations
5. Test thoroughly in production environment

## Troubleshooting

### Common Issues

1. **"Invalid client" error**
   - Check if Client ID is correct
   - Verify authorized origins and redirect URIs

2. **"Token verification failed"**
   - Ensure token is not expired
   - Verify the token is from the correct Google account

3. **"API not enabled"**
   - Go to Google Cloud Console and enable required APIs

4. **Mobile app sign-in fails**
   - Check package name/bundle ID matches exactly
   - For Android: Verify SHA-1 certificate fingerprint

### Debug Commands

```bash
# Test Google API connectivity
php artisan tinker
# Then run:
$client = new \Google_Client(['client_id' => config('services.google.client_id')]);
echo "Google Client created successfully";

# Check API routes
php artisan route:list --path=auth/social

# Clear config cache
php artisan config:clear
php artisan config:cache
```

## Security Notes

1. **Never commit real credentials** to version control
2. **Use HTTPS** in production for all OAuth flows
3. **Rotate credentials** regularly
4. **Monitor API usage** in Google Cloud Console
5. **Implement rate limiting** on your API endpoints

## Support

For issues:
1. Check Google Cloud Console error logs
2. Review Laravel logs in `storage/logs/`
3. Test with Google's OAuth playground
4. Verify all configuration steps are completed

Remember to replace `your_google_client_id_here` and `your_google_client_secret_here` with your actual Google OAuth credentials!
