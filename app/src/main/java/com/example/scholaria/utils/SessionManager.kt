package com.example.scholaria.utils

import android.content.Context
import android.content.SharedPreferences

class SessionManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("auth", Context.MODE_PRIVATE)

    fun saveAuthToken(token: String) {
        val editor = prefs.edit()
        editor.putString("token", token)
        editor.apply()
    }

    fun fetchAuthToken(): String? {
        return prefs.getString("token", null)
    }

    fun clearAuthToken() {
        val editor = prefs.edit()
        editor.remove("token")
        editor.apply()
    }
}
