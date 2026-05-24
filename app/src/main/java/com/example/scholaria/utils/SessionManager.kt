package com.example.scholaria.utils

import android.content.Context

class SessionManager(context: Context) {
    private val tokenManager = TokenManager(context)

    fun saveAuthToken(token: String) {
        tokenManager.saveToken(token)
    }

    fun fetchAuthToken(): String? {
        return tokenManager.getToken()
    }

    fun clearAuthToken() {
        tokenManager.clearToken()
    }

    fun isLoggedIn(): Boolean {
        return tokenManager.isLoggedIn()
    }
}
