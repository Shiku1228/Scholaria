package com.example.scholaria.utils

import android.content.Context
import android.content.SharedPreferences
import androidx.core.content.edit

class TokenManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("auth", Context.MODE_PRIVATE)

    fun saveToken(token: String) {
        prefs.edit {
            putString("jwt_token", token)
        }
    }

    fun getToken(): String? {
        return prefs.getString("jwt_token", null)
    }

    fun clearToken() {
        prefs.edit {
            remove("jwt_token")
        }
    }

    fun isLoggedIn(): Boolean {
        return getToken() != null
    }
}
