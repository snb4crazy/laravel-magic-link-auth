# Flutter/Mobile Integration Examples

This guide shows how a Flutter app can use this package for passwordless login.

## Overview

Flow:

1. User enters email in app.
2. App calls backend `POST /api/auth/magic-link/request`.
3. User taps link in email.
4. App receives deep link with `email` + `token`.
5. App calls backend `POST /api/auth/magic-link/exchange`.
6. App stores bearer token and uses it for authenticated API calls.

## 1) Backend deep-link configuration

Set a mobile deep-link URL in Laravel:

```dotenv
MAGIC_LINK_FRONTEND_URL=myapp://auth/magic-link
MAGIC_LINK_ISSUE_SANCTUM_TOKEN=true
```

The package appends query params:

```text
myapp://auth/magic-link?email=user@example.com&token=...
```

## 2) Flutter dependencies

In `pubspec.yaml`:

```yaml
dependencies:
  dio: ^5.7.0
  app_links: ^6.3.0
  flutter_secure_storage: ^9.2.2
```

## 3) Request magic link from Flutter

```dart
import 'package:dio/dio.dart';

final dio = Dio(BaseOptions(baseUrl: 'https://api.example.com'));

Future<void> requestMagicLink(String email) async {
  await dio.post('/api/auth/magic-link/request', data: {
    'email': email,
  });
}
```

Always show a neutral success UI like:  
`"If that email is registered, a magic link has been sent."`

## 4) Handle incoming deep link

```dart
import 'package:app_links/app_links.dart';

final appLinks = AppLinks();

void listenForMagicLinks(void Function(String email, String token) onReceived) {
  appLinks.uriLinkStream.listen((uri) {
    if (uri.scheme != 'myapp' || uri.host != 'auth') return;
    if (uri.path != '/magic-link') return;

    final email = uri.queryParameters['email'];
    final token = uri.queryParameters['token'];

    if (email == null || token == null) return;
    onReceived(email, token);
  });
}
```

## 5) Exchange link for API token

```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

final dio = Dio(BaseOptions(baseUrl: 'https://api.example.com'));
const secureStorage = FlutterSecureStorage();

Future<void> exchangeMagicLink({
  required String email,
  required String token,
}) async {
  final response = await dio.post('/api/auth/magic-link/exchange', data: {
    'email': email,
    'token': token,
  });

  final accessToken = response.data['token'] as String?;
  if (accessToken == null) {
    throw Exception('Token missing in exchange response');
  }

  await secureStorage.write(key: 'access_token', value: accessToken);
}
```

## 6) Use token for authenticated requests

```dart
Future<Response<dynamic>> getProfile() async {
  final token = await secureStorage.read(key: 'access_token');
  if (token == null) throw Exception('Not authenticated');

  return dio.get(
    '/api/me',
    options: Options(headers: {'Authorization': 'Bearer $token'}),
  );
}
```

## 7) Error handling UX recommendations

- `422` from `/exchange`: show `"Link invalid or expired. Request a new one."`
- `403` with `email_verification_required=true`: show verification instructions.
- Network failures: keep a retry button and allow requesting a new link.

## 8) Security recommendations for mobile

- Store access token in secure storage (not shared preferences/plain local files).
- Never log full deep-link URL in production logs (it contains one-time token).
- Add token cleanup on logout.
- Consider `MAGIC_LINK_REVOKE_EXISTING_TOKENS=true` if you want one active device token per user.
