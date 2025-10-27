# Google OAuth Login for Mobile Apps

This document explains how to integrate Google OAuth login for mobile applications with the Perfect Fit API.

## Overview

The flow works as follows:
1. Mobile app authenticates with Google using Google's SDK
2. Mobile app receives a Google ID token
3. Mobile app sends the ID token to the Perfect Fit API
4. API validates the token and creates/updates user account
5. API returns user session token for subsequent API calls

## Complete OAuth Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    🎯 GOOGLE OAUTH FLOW                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   📱 Mobile App │    │   🔐 Google SDK  │    │   🖥️  Perfect   │
│                 │    │                  │    │     Fit API     │
├─────────────────┤    ├──────────────────┤    ├─────────────────┤
│                 │    │                  │    │                 │
│ 1. 👆 User taps │───▶│ 2. 📋 Show       │    │                 │
│    "Sign in     │    │    Google        │    │                 │
│    with Google" │    │    Sign-In UI    │    │                 │
│                 │    │    (Scopes:      │    │                 │
│                 │    │     profile,     │    │                 │
│                 │    │     email)       │    │                 │
│                 │    │                  │    │                 │
│ 3. 🔑 User      │◀───│ 4. ✅ Return     │    │                 │
│    selects      │    │    ID Token      │    │                 │
│    account &    │    │    + Access      │    │                 │
│    grants       │    │    Token         │    │                 │
│    permissions  │    │                  │    │                 │
├─────────────────┤    ├──────────────────┤    ├─────────────────┤
│                 │    │                  │    │ 5. 🔍 Verify    │
│ 6. 📤 Send ID   │───▶│                  │───▶│    Token with   │
│    Token +      │    │                  │    │    Google       │
│    Device Info  │    │                  │    │    Client       │
│    📱 Device ID │    │                  │    │    Library      │
│    📱 Device    │    │                  │    │                 │
│    Name/Type    │    │                  │    │ 6. ✅ Validate  │
│    📱 OS/App    │    │                  │    │    Signature    │
│    Version      │    │                  │    │    Audience     │
│    📱 FCM Token │    │                  │    │    Expiration   │
│                 │    │                  │    │                 │
├─────────────────┤    ├──────────────────┤    ├─────────────────┤
│                 │    │                  │    │ 7. 👤 Extract   │
│                 │    │                  │    │    User Data    │
│                 │    │                  │    │    from Token   │
│                 │    │                  │    │                 │
│                 │    │                  │    │ 8. 🔍 Find or   │
│                 │    │                  │    │    Create User  │
│                 │    │                  │    │    Account      │
├─────────────────┤    ├──────────────────┤    ├─────────────────┤
│                 │    │                  │    │ 9. 📱 Register  │
│                 │    │                  │    │    Device       │
│                 │    │                  │    │    (if provided)│
│                 │    │                  │    │                 │
│ 10. 💾 Store    │◀───│                  │◀───│ 11. 🔑 Generate │
│     API Token   │    │                  │    │     API Token   │
│     & User      │    │                  │    │     (Passport)  │
│     Data        │    │                  │    │                 │
│     Securely    │    │                  │    │ 12. 📤 Return   │
│                 │    │                  │    │     Response    │
│                 │    │                  │    │                 │
│ 13. 🚀 Navigate │    │                  │    │                 │
│     to Main     │    │                  │    │                 │
│     App with    │    │                  │    │                 │
│     Auth User   │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

### Mobile App → API Request Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                📨 API REQUEST FLOW                               │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐    ┌─────────────────────────────────────────┐
│ 📱 Mobile    │───▶│ POST /api/auth/social/google           │
│    App       │    │ Headers: Content-Type: application/json │
│              │    │ Body: {                                 │
└──────────────┘    │   "token": "eyJhbGciOiJSUzI1NiIs...",   │
                    │   "device_id": "abc123",                │
                    │   "device_name": "Samsung Galaxy S23",  │
                    │   "device_type": "Android",             │
                    │   "device_model": "SM-S911B",           │
                    │   "os_version": "13",                   │
                    │   "app_version": "1.0.0",               │
                    │   "fcm_token": "fcm_token_here"         │
                    │ }                                       │
                    └─────────────────────────────────────────┘
                                          │
                                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                🔐 TOKEN VERIFICATION                            │
└─────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────────┐
                    │ Google_Client::verifyIdToken($token)    │
                    │ ✓ Verify token signature                │
                    │ ✓ Check audience (aud) matches client_id │
                    │ ✓ Check expiration (exp)                │
                    │ ✓ Extract payload data                  │
                    └─────────────────────────────────────────┘
                                          │
                                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                👤 USER MANAGEMENT                               │
└─────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────────┐
                    │ 1. Find user by google_id               │
                    │ 2. If not found, find by email          │
                    │ 3. If found, link Google account        │
                    │ 4. If not found, create new user        │
                    │ 5. Update avatar and email verification │
                    └─────────────────────────────────────────┘
                                          │
                                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                🔑 SESSION CREATION                              │
└─────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────────┐
                    │ 1. Generate Laravel Passport token      │
                    │ 2. Register/update device record        │
                    │ 3. Set device as trusted (if first)     │
                    │ 4. Update last used timestamp           │
                    └─────────────────────────────────────────┘
                                          │
                                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                📨 API RESPONSE                                  │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐    ┌─────────────────────────────────────────┐
│ 📱 Mobile    │◀───│ HTTP 200 OK                             │
│    App       │    │ {                                       │
│              │    │   "success": true,                      │
└──────────────┘    │   "message": "Login successful",        │
                    │   "data": {                             │
                    │     "user": {                           │
                    │       "id": 123,                        │
                    │       "name": "John Doe",               │
                    │       "email": "john.doe@gmail.com",    │
                    │       "role": "user",                   │
                    │       "avatar": "https://..."           │
                    │     },                                  │
                    │     "token": "1|abc123def456...",       │
                    │     "token_type": "Bearer",             │
                    │     "expires_at": "2025-10-28 10:30:00" │
                    │   }                                     │
                    │ }                                       │
                    └─────────────────────────────────────────┘
```

### Error Handling Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                ❌ ERROR SCENARIOS                                │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐    ┌─────────────────────────────────────────┐
│ Invalid Token │───▶│ HTTP 401 Unauthorized                   │
│ (expired,     │    │ {                                       │
│  malformed,   │    │   "success": false,                     │
│  wrong        │    │   "message": "Invalid Google token"     │
│  audience)    │    │ }                                       │
└──────────────┘    └─────────────────────────────────────────┘
         │
         ▼
┌──────────────┐    ┌─────────────────────────────────────────┐
│ Validation   │───▶│ HTTP 422 Validation Error               │
│ Errors       │    │ {                                       │
│ (missing     │    │   "success": false,                     │
│  fields,     │    │   "message": "Validation error",        │
│  invalid     │    │   "errors": {                           │
│  format)     │    │     "token": ["required", "string"]     │
└──────────────┘    │   }                                     │
                    └─────────────────────────────────────────┘
         │
         ▼
┌──────────────┐    ┌─────────────────────────────────────────┐
│ Server       │───▶│ HTTP 500 Internal Server Error          │
│ Errors       │    │ {                                       │
│ (database,   │    │   "success": false,                     │
│  Google API  │    │   "message": "Google login failed",     │
│  failures)   │    │   "error": "Error details"              │
└──────────────┘    └─────────────────────────────────────────┘
```

### Platform-Specific Implementation Flows

#### Android Flow
```
┌─────────────────────────────────────────────────────────────────┐
│                    🤖 ANDROID FLOW                               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────┐    ┌─────────────────────────────────────────┐
│ 📱 Activity │───▶│ GoogleSignInOptions.Builder()           │
│             │    │ .requestIdToken("WEB_CLIENT_ID")        │
│             │    │ .requestEmail()                         │
│             │    │ .build()                                │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ SDK Call    │───▶│ GoogleSignIn.getClient(activity, gso)   │
│             │    │ .signInIntent                           │
│             │    │ startActivityForResult()                │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ Result      │◀───│ GoogleSignInAccount account             │
│ Handler     │    │ account.idToken ← ID TOKEN              │
│             │    │ account.email                           │
└─────────────┘    └─────────────────────────────────────────┘
```

#### iOS Flow
```
┌─────────────────────────────────────────────────────────────────┐
│                    🍎 iOS FLOW                                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────┐    ┌─────────────────────────────────────────┐
│ ViewCtrl    │───▶│ GIDConfiguration(clientID: CLIENT_ID)   │
│             │    │ GIDSignIn.sharedInstance.configuration  │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ SDK Call    │───▶│ GIDSignIn.sharedInstance.signIn()       │
│             │    │ withPresenting: viewController          │
│             │    │ completion: { result, error in ... }    │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ Result      │◀───│ result?.user.idToken?.tokenString       │
│ Handler     │    │ ← ID TOKEN                              │
│             │    │ result?.user.profile?.name              │
└─────────────┘    └─────────────────────────────────────────┘
```

#### React Native Flow
```
┌─────────────────────────────────────────────────────────────────┐
│                    ⚛️  REACT NATIVE FLOW                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────┐    ┌─────────────────────────────────────────┐
│ Component   │───▶│ GoogleSignin.configure({                │
│             │    │   webClientId: 'CLIENT_ID',             │
│             │    │   offlineAccess: false                  │
│             │    │ })                                      │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ Auth Call   │───▶│ await GoogleSignin.signIn()             │
│             │    │ const tokens = await GoogleSignin       │
│             │    │   .getTokens()                          │
└─────────────┘    └─────────────────────────────────────────┘
        │
        ▼
┌─────────────┐    ┌─────────────────────────────────────────┐
│ Result      │◀───│ tokens.idToken ← ID TOKEN               │
│ Handler     │    │ userInfo.user.email                     │
│             │    │ userInfo.user.name                      │
└─────────────┘    └─────────────────────────────────────────┘
```

### State Machine Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                🔄 OAUTH STATE MACHINE                           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   📱 IDLE   │───▶│  🔄 INIT    │───▶│  🔐 AUTH    │───▶│  ✅ SUCCESS │
│   (No auth) │    │ (SDK init)  │    │ (Google UI) │    │  (Logged in)│
│             │    │             │    │             │    │             │
│ • App start │    │ • Configure │    │ • Show      │    │ • Store     │
│ • No token  │    │   SDK       │    │   login UI  │    │   token     │
│ • Show login│    │ • Set scopes│    │ • Request   │    │ • Navigate  │
│   button    │    │ • Client ID │    │   perms     │    │   to app    │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
        │                 │                 │                 │
        │                 │                 │                 │
        ▼                 ▼                 ▼                 ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   ❌ ERROR  │◀───│  🚫 CANCEL  │◀───│  ❌ INVALID │◀───│  ⚠️  EXPIRE │
│  (Network)  │    │  (User UX)  │    │  (Bad token)│    │  (Token old)│
│             │    │             │    │             │    │             │
│ • Retry     │    │ • Back to   │    │ • Show      │    │ • Refresh   │
│ • Show      │    │   login     │    │   error     │    │ • Redirect  │
│ • Back to   │    │ • Reset     │    │ • Retry     │    │   to login  │
│   login     │    │   state     │    │   option    │    │ • Clear     │
│             │    │             │    │             │    │   cache     │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

### Database Schema Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                💾 DATABASE OPERATIONS                            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   👤 Users      │    │   🔗 User       │    │   🏷️  Tokens    │
│   Table         │    │   Devices       │    │   (Passport)    │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│                 │    │                 │    │                 │
│ • id            │    │ • id            │    │ • id            │
│ • name          │    │ • user_id       │    │ • user_id       │
│ • email         │    │ • device_id     │    │ • name          │
│ • google_id     │    │ • device_name   │    │ • token         │
│ • facebook_id   │    │ • device_type   │    │ • abilities     │
│ • tiktok_id     │    │ • device_model  │    │ • expires_at    │
│ • avatar_url    │    │ • os_version    │    │ • created_at    │
│ • role          │    │ • app_version   │    │ • updated_at    │
│ • status        │    │ • fcm_token     │    │                 │
│ • email_verified│    │ • ip_address    │    │                 │
│ • created_at    │    │ • user_agent    │    │                 │
│ • updated_at    │    │ • last_used_at  │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
        │                 │                 │
        │                 │                 │
        ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                🔄 USER CREATION/LINKING                         │
└─────────────────────────────────────────────────────────────────┘

Step 1: Find by google_id → NOT FOUND
Step 2: Find by email     → FOUND (existing user)
Step 3: Link Google ID   → UPDATE user record
Step 4: Update avatar    → UPDATE avatar_url
Step 5: Mark verified    → SET email_verified_at

OR (if no existing user):
Step 1: Find by google_id → NOT FOUND
Step 2: Find by email     → NOT FOUND
Step 3: Create new user   → INSERT into users
Step 4: Set Google ID     → SET google_id
Step 5: Set avatar        → SET avatar_url
Step 6: Mark verified     → SET email_verified_at
Step 7: Generate token    → CREATE passport token
```

## Quick Reference Guide

```
┌─────────────────────────────────────────────────────────────────┐
│                🚀 QUICK START SUMMARY                           │
└─────────────────────────────────────────────────────────────────┘

📱 MOBILE APP → 🔐 GOOGLE → 🖥️  API → ✅ AUTHENTICATED

1️⃣  Mobile: GoogleSignIn.getClient() → Get ID token
2️⃣  Mobile: POST /api/auth/social/google → Send token + device info
3️⃣  API: Google_Client::verifyIdToken() → Validate token
4️⃣  API: Find/create user account → Link Google account
5️⃣  API: Generate Passport token → Return auth credentials
6️⃣  Mobile: Store token securely → Navigate to app

⚠️  IMPORTANT:
• Use WEB CLIENT ID (not mobile client secret)
• Include device_id for multi-device support
• Token expires - handle refresh in mobile app
• Verify email is included in Google token
• Check logs for debugging OAuth issues
```

## 1. Google OAuth Setup

### 1.1 Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API (or Google People API)
4. Go to "Credentials" and create OAuth 2.0 Client IDs
5. For mobile apps, choose "Android" or "iOS" application type
6. Add your app's package name and SHA-1 certificate fingerprint

### 1.2 Configure API Credentials

Update your Laravel `.env` file with your Google OAuth credentials:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID="your_google_client_id_here"
GOOGLE_CLIENT_SECRET="your_google_client_secret_here"
GOOGLE_REDIRECT_URL="http://localhost:8000/auth/google/callback"
```

## 2. Mobile App Implementation

### 2.1 Android Implementation

#### Add Dependencies

Add Google Play Services Auth to your `build.gradle`:

```gradle
implementation 'com.google.android.gms:play-services-auth:20.7.0'
```

#### Google Sign-In Implementation

```kotlin
class GoogleAuthHelper(private val activity: Activity) {
    private val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
        .requestIdToken("YOUR_WEB_CLIENT_ID") // Use web client ID for server verification
        .requestEmail()
        .build()

    private val googleSignInClient = GoogleSignIn.getClient(activity, gso)

    fun signIn() {
        val signInIntent = googleSignInClient.signInIntent
        activity.startActivityForResult(signInIntent, RC_SIGN_IN)
    }

    fun handleSignInResult(completedTask: Task<GoogleSignInAccount>) {
        try {
            val account = completedTask.getResult(ApiException::class.java)
            val idToken = account.idToken

            if (idToken != null) {
                // Send token to your API
                loginToAPI(idToken)
            }
        } catch (e: ApiException) {
            // Handle error
        }
    }

    private fun loginToAPI(idToken: String) {
        // Send POST request to your API
        val jsonObject = JSONObject().apply {
            put("token", idToken)
            put("device_id", getDeviceId()) // Unique device identifier
            put("device_name", "Samsung Galaxy S23")
            put("device_type", "Android")
            put("device_model", "SM-S911B")
            put("os_version", "13")
            put("app_version", "1.0.0")
        }

        // Make API call to: POST /api/auth/social/google
        // Handle response and store authentication token
    }
}
```

#### Alternative: Firebase Authentication (Recommended)

```kotlin
// Add Firebase Auth dependency
implementation 'com.google.firebase:firebase-auth:22.3.1'

class FirebaseAuthHelper {
    private val auth = Firebase.auth

    fun signInWithGoogle(activity: Activity) {
        val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
            .requestIdToken(getString(R.string.default_web_client_id))
            .requestEmail()
            .build()

        val googleSignInClient = GoogleSignIn.getClient(activity, gso)
        val signInIntent = googleSignInClient.signInIntent
        activity.startActivityForResult(signInIntent, RC_SIGN_IN)
    }

    fun handleSignInResult(account: GoogleSignInAccount?) {
        val credential = GoogleAuthProvider.getCredential(account?.idToken, null)

        auth.signInWithCredential(credential)
            .addOnCompleteListener { task ->
                if (task.isSuccessful) {
                    val user = auth.currentUser
                    val idToken = account?.idToken

                    if (idToken != null) {
                        loginToAPI(idToken)
                    }
                }
            }
    }

    private fun loginToAPI(idToken: String) {
        // Send token to Perfect Fit API
        // POST /api/auth/social/google
    }
}
```

### 2.2 iOS Implementation (Swift)

#### Add Dependencies

Add Google Sign-In SDK via CocoaPods:

```ruby
pod 'GoogleSignIn'
```

#### Google Sign-In Implementation

```swift
import GoogleSignIn

class GoogleAuthManager {
    static let shared = GoogleAuthManager()

    func signIn(viewController: UIViewController) {
        let signInConfig = GIDConfiguration(clientID: "YOUR_WEB_CLIENT_ID")
        GIDSignIn.sharedInstance.configuration = signInConfig

        GIDSignIn.sharedInstance.signIn(withPresenting: viewController) { result, error in
            guard error == nil else { return }

            if let idToken = result?.user.idToken?.tokenString {
                self.loginToAPI(idToken: idToken)
            }
        }
    }

    private func loginToAPI(idToken: String) {
        let parameters: [String: Any] = [
            "token": idToken,
            "device_id": UIDevice.current.identifierForVendor?.uuidString ?? "",
            "device_name": UIDevice.current.name,
            "device_type": "iOS",
            "device_model": getDeviceModel(),
            "os_version": UIDevice.current.systemVersion,
            "app_version": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? ""
        ]

        // Make API call to: POST /api/auth/social/google
        NetworkManager.shared.post(endpoint: "/auth/social/google", parameters: parameters) { result in
            switch result {
            case .success(let response):
                // Handle successful login
                self.handleLoginSuccess(response)
            case .failure(let error):
                // Handle error
                print("Login failed: \(error)")
            }
        }
    }

    private func handleLoginSuccess(_ response: [String: Any]) {
        if let token = response["token"] as? String,
           let user = response["user"] as? [String: Any] {
            // Store token and user data
            UserDefaults.standard.set(token, forKey: "auth_token")
            // Navigate to main app
        }
    }
}
```

### 2.3 React Native Implementation

#### Install Dependencies

```bash
npm install @react-native-async-storage/async-storage react-native-google-signin
# or
npm install @react-native-firebase/auth
```

#### Google Sign-In Implementation

```javascript
import { GoogleSignin } from '@react-native-google-signin/google-signin';

class AuthService {
  constructor() {
    GoogleSignin.configure({
      webClientId: 'YOUR_WEB_CLIENT_ID', // From Google Cloud Console
      offlineAccess: false,
    });
  }

  async signIn() {
    try {
      await GoogleSignin.hasPlayServices();
      const userInfo = await GoogleSignin.signIn();
      const { idToken } = await GoogleSignin.getTokens();

      if (idToken) {
        return await this.loginToAPI(idToken);
      }
    } catch (error) {
      console.error('Google Sign-In error:', error);
      throw error;
    }
  }

  async loginToAPI(idToken) {
    const deviceInfo = await this.getDeviceInfo();

    const response = await fetch('https://your-api.com/api/auth/social/google', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        token: idToken,
        device_id: deviceInfo.deviceId,
        device_name: deviceInfo.deviceName,
        device_type: deviceInfo.deviceType,
        device_model: deviceInfo.deviceModel,
        os_version: deviceInfo.osVersion,
        app_version: deviceInfo.appVersion,
      }),
    });

    if (!response.ok) {
      throw new Error('Login failed');
    }

    const data = await response.json();
    return data;
  }

  async getDeviceInfo() {
    const deviceId = await AsyncStorage.getItem('device_id') ||
                    `device_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

    // Store device ID for future use
    AsyncStorage.setItem('device_id', deviceId);

    return {
      deviceId,
      deviceName: DeviceInfo.getDeviceName(),
      deviceType: Platform.OS,
      deviceModel: DeviceInfo.getModel(),
      osVersion: DeviceInfo.getSystemVersion(),
      appVersion: DeviceInfo.getVersion(),
    };
  }
}
```

## 3. API Integration

### 3.1 API Endpoint

**POST** `/api/auth/social/google`

### 3.2 Request Format

```json
{
  "token": "google_id_token_here",
  "device_id": "unique_device_identifier",
  "device_name": "Samsung Galaxy S23",
  "device_type": "Android",
  "device_model": "SM-S911B",
  "os_version": "13",
  "app_version": "1.0.0",
  "fcm_token": "firebase_cloud_messaging_token" // Optional
}
```

### 3.3 Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | Google ID token from mobile SDK |
| `device_id` | string | No | Unique device identifier |
| `device_name` | string | No | Human-readable device name |
| `device_type` | string | No | Device platform (Android/iOS) |
| `device_model` | string | No | Device model identifier |
| `os_version` | string | No | Operating system version |
| `app_version` | string | No | Application version |
| `fcm_token` | string | No | Firebase Cloud Messaging token |

### 3.4 Response Format

#### Success Response (200)

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john.doe@gmail.com",
      "role": "user",
      "avatar": "https://lh3.googleusercontent.com/..."
    },
    "token": "1|abc123def456...",
    "token_type": "Bearer",
    "expires_at": "2025-10-28 10:30:00"
  }
}
```

#### Error Response (422/401)

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "token": ["The token field is required."]
  }
}
```

## 4. Authentication Flow

### 4.1 Complete Mobile App Flow

1. **User clicks "Sign in with Google"**
2. **Mobile app opens Google authentication**
3. **User selects Google account and grants permissions**
4. **Google SDK returns ID token**
5. **Mobile app sends ID token to API**
6. **API validates token with Google servers**
7. **API creates/updates user account**
8. **API creates device record**
9. **API returns session token**
10. **Mobile app stores session token**
11. **Mobile app navigates to main app**

### 4.2 Session Management

After successful login, store the returned token:

```javascript
// Store in AsyncStorage (React Native)
await AsyncStorage.setItem('auth_token', response.data.token);
await AsyncStorage.setItem('user_data', JSON.stringify(response.data.user));

// Use in API requests
const apiClient = axios.create({
  baseURL: 'https://your-api.com/api',
  headers: {
    Authorization: `Bearer ${token}`,
  },
});
```

### 4.3 Token Usage

Use the returned token for all subsequent API requests:

```javascript
// Example API call with token
const response = await fetch('https://your-api.com/api/user/profile', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${storedToken}`,
    'Content-Type': 'application/json',
  },
});
```

## 5. Security Considerations

### 5.1 Token Validation

The API validates the Google ID token on the server side using Google's client libraries to ensure authenticity.

### 5.2 Device Management

- Each device login creates a device record
- Supports multiple devices per user
- Device trust management
- Session revocation capabilities

### 5.3 Token Security

- Tokens are generated using Laravel Passport/Sanctum
- Include expiration timestamps
- Can be revoked per device
- Support for refresh tokens

## 6. Testing

### 6.1 Manual Testing

Test the API endpoint using curl:

```bash
curl -X POST http://localhost:8000/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{
    "token": "google_id_token_here",
    "device_id": "test_device_123",
    "device_name": "Test Device",
    "device_type": "Android"
  }'
```

### 6.2 Google ID Token Generation

For testing, you can use Google's OAuth 2.0 playground:

1. Go to [OAuth 2.0 Playground](https://developers.google.com/oauthplayground)
2. Select "Google+ API v1" scope
3. Authorize and exchange for tokens
4. Use the returned ID token in your API requests

## 7. Error Handling

### 7.1 Common Errors

```javascript
// Handle different error scenarios
switch (error.response?.status) {
  case 401:
    // Invalid token
    break;
  case 422:
    // Validation errors
    break;
  case 500:
    // Server error
    break;
  default:
    // Network or other errors
}
```

### 7.2 Token Refresh

Implement token refresh logic for long-lived sessions:

```javascript
// Check if token is expired
if (isTokenExpired(token)) {
  try {
    const newToken = await refreshToken();
    await AsyncStorage.setItem('auth_token', newToken);
  } catch (error) {
    // Redirect to login
  }
}
```

## 8. Configuration Checklist

- [ ] Google OAuth credentials configured in `.env`
- [ ] Google Cloud Console project set up
- [ ] OAuth consent screen configured
- [ ] Mobile app package name/bundle ID registered
- [ ] Test devices authorized (if in testing mode)
- [ ] API routes accessible and tested
- [ ] Device management features working
- [ ] Error handling implemented

## 9. Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Verify Google Cloud Console configuration
- Test with Google's OAuth playground
- Review API documentation in `/docs` folder
