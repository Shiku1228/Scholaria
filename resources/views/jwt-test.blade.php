<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JWT Authentication Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">JWT Token Test</h1>
        
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">1. Test Login</h2>
            <p class="text-gray-600 mb-4">Use valid credentials to get a JWT token:</p>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600"><strong>Email:</strong> test@example.com</p>
                <p class="text-sm text-gray-600"><strong>Password:</strong> password123</p>
            </div>
            
            <button onclick="testLogin()" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Test Login
            </button>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">2. Test Protected Route</h2>
            <p class="text-gray-600 mb-4">Access a protected route with the JWT token:</p>
            
            <button onclick="testProtectedRoute()" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Test Protected Route
            </button>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">3. Test Token Validation</h2>
            <p class="text-gray-600 mb-4">Validate the current JWT token:</p>
            
            <button onclick="testTokenValidation()" class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Validate Token
            </button>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">4. Test Token Refresh</h2>
            <p class="text-gray-600 mb-4">Refresh the JWT token:</p>
            
            <button onclick="testTokenRefresh()" class="w-full bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Refresh Token
            </button>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">5. Test Logout</h2>
            <p class="text-gray-600 mb-4">Logout and invalidate the token:</p>
            
            <button onclick="testLogout()" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Test Logout
            </button>
        </div>

        <!-- Results Display -->
        <div id="results" class="mt-6 hidden">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Results:</h3>
            <div id="result-content" class="bg-gray-50 p-4 rounded text-sm"></div>
        </div>
    </div>

    <script>
        let currentToken = localStorage.getItem('jwt_token');
        
        function showResults(title, content, isSuccess = true) {
            const resultsDiv = document.getElementById('results');
            const resultContent = document.getElementById('result-content');
            
            resultsDiv.classList.remove('hidden');
            resultContent.innerHTML = `
                <div class="mb-3">
                    <h4 class="font-semibold ${isSuccess ? 'text-green-600' : 'text-red-600'}">${title}</h4>
                </div>
                <div class="bg-${isSuccess ? 'green' : 'red'}-50 border border-${isSuccess ? 'green' : 'red'}-200 p-3 rounded">
                    <pre class="text-xs overflow-x-auto">${JSON.stringify(content, null, 2)}</pre>
                </div>
            `;
        }

        async function testLogin() {
            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: 'test@example.com',
                        password: 'password123'
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('jwt_token', data.token);
                    currentToken = data.token;
                    showResults('Login Successful', data, true);
                } else {
                    showResults('Login Failed', data, false);
                }
            } catch (error) {
                showResults('Login Error', { error: error.message }, false);
            }
        }

        async function testProtectedRoute() {
            if (!currentToken) {
                showResults('No Token', { error: 'Please login first' }, false);
                return;
            }

            try {
                const response = await fetch('/api/me', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    showResults('Protected Route Access', data, true);
                } else {
                    showResults('Protected Route Failed', data, false);
                }
            } catch (error) {
                showResults('Protected Route Error', { error: error.message }, false);
            }
        }

        async function testTokenValidation() {
            if (!currentToken) {
                showResults('No Token', { error: 'Please login first' }, false);
                return;
            }

            try {
                const response = await fetch('/api/validate', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                showResults('Token Validation', data, data.success);
            } catch (error) {
                showResults('Token Validation Error', { error: error.message }, false);
            }
        }

        async function testTokenRefresh() {
            if (!currentToken) {
                showResults('No Token', { error: 'Please login first' }, false);
                return;
            }

            try {
                const response = await fetch('/api/refresh', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('jwt_token', data.token);
                    currentToken = data.token;
                    showResults('Token Refresh', data, true);
                } else {
                    showResults('Token Refresh Failed', data, false);
                }
            } catch (error) {
                showResults('Token Refresh Error', { error: error.message }, false);
            }
        }

        async function testLogout() {
            if (!currentToken) {
                showResults('No Token', { error: 'Please login first' }, false);
                return;
            }

            try {
                const response = await fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    localStorage.removeItem('jwt_token');
                    currentToken = null;
                    showResults('Logout Successful', data, true);
                } else {
                    showResults('Logout Failed', data, false);
                }
            } catch (error) {
                showResults('Logout Error', { error: error.message }, false);
            }
        }

        // Display current token status on load
        if (currentToken) {
            showResults('Current Token', { 
                token: currentToken.substring(0, 50) + '...', 
                stored: 'Token is stored in localStorage' 
            }, true);
        }
    </script>
</body>
</html>
