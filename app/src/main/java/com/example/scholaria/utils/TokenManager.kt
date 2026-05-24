package com.example.scholaria.utils

import android.content.Context
import android.content.SharedPreferences
import android.util.Base64
import androidx.core.content.edit
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties

class TokenManager(context: Context) {
    private val appContext = context.applicationContext
    private val prefs: SharedPreferences = appContext.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    companion object {
        private const val PREF_NAME = "auth"
        private const val TOKEN_KEY = "jwt_token"
        private const val ENCRYPTED_TOKEN_KEY = "jwt_token_encrypted"
        private const val ENCRYPTED_IV_KEY = "jwt_token_iv"
        private const val ANDROID_KEYSTORE = "AndroidKeyStore"
        private const val KEY_ALIAS = "scholaria_jwt_key"
        private const val AES_MODE = "AES/GCM/NoPadding"
        private const val GCM_TAG_LENGTH = 128
    }

    fun saveToken(token: String) {
        val encryptedToken = encrypt(token)
        prefs.edit {
            putString(ENCRYPTED_TOKEN_KEY, encryptedToken.cipherText)
            putString(ENCRYPTED_IV_KEY, encryptedToken.iv)
            remove(TOKEN_KEY)
        }
    }

    fun getToken(): String? {
        val encryptedToken = prefs.getString(ENCRYPTED_TOKEN_KEY, null)
        val iv = prefs.getString(ENCRYPTED_IV_KEY, null)
        if (!encryptedToken.isNullOrBlank() && !iv.isNullOrBlank()) {
            return decrypt(encryptedToken, iv)
        }

        val legacyToken = prefs.getString(TOKEN_KEY, null)
        if (!legacyToken.isNullOrBlank()) {
            saveToken(legacyToken)
            return legacyToken
        }

        return null
    }

    fun clearToken() {
        prefs.edit {
            remove(ENCRYPTED_TOKEN_KEY)
            remove(ENCRYPTED_IV_KEY)
            remove(TOKEN_KEY)
        }
    }

    fun isLoggedIn(): Boolean {
        return !getToken().isNullOrBlank()
    }

    fun saveAuthToken(token: String) {
        saveToken(token)
    }

    fun fetchAuthToken(): String? {
        return getToken()
    }

    fun clearAuthToken() {
        clearToken()
    }

    private fun encrypt(plainText: String): EncryptedToken {
        val cipher = Cipher.getInstance(AES_MODE)
        cipher.init(Cipher.ENCRYPT_MODE, getOrCreateSecretKey())
        val cipherText = cipher.doFinal(plainText.toByteArray(Charsets.UTF_8))
        val iv = Base64.encodeToString(cipher.iv, Base64.NO_WRAP)
        val encodedCipherText = Base64.encodeToString(cipherText, Base64.NO_WRAP)
        return EncryptedToken(encodedCipherText, iv)
    }

    private fun decrypt(cipherText: String, iv: String): String? {
        return try {
            val cipher = Cipher.getInstance(AES_MODE)
            val ivBytes = Base64.decode(iv, Base64.NO_WRAP)
            val cipherBytes = Base64.decode(cipherText, Base64.NO_WRAP)
            val spec = GCMParameterSpec(GCM_TAG_LENGTH, ivBytes)
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateSecretKey(), spec)
            val plainBytes = cipher.doFinal(cipherBytes)
            String(plainBytes, Charsets.UTF_8)
        } catch (_: Exception) {
            clearToken()
            null
        }
    }

    private fun getOrCreateSecretKey(): SecretKey {
        val keyStore = KeyStore.getInstance(ANDROID_KEYSTORE).apply {
            load(null)
        }

        val existingKey = keyStore.getKey(KEY_ALIAS, null)
        if (existingKey is SecretKey) {
            return existingKey
        }

        val keyGenerator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, ANDROID_KEYSTORE)
        val keySpec = KeyGenParameterSpec.Builder(
            KEY_ALIAS,
            KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
        )
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build()

        keyGenerator.init(keySpec)
        return keyGenerator.generateKey()
    }

    private data class EncryptedToken(
        val cipherText: String,
        val iv: String
    )
}
