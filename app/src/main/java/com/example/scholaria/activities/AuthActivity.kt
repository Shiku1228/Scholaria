package com.example.scholaria.activities

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.ProgressBar
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.lifecycle.lifecycleScope
import com.example.scholaria.R
import com.example.scholaria.networks.ApiClient
import com.example.scholaria.networks.LoginRequest
import com.example.scholaria.utils.TokenManager
import com.google.android.material.button.MaterialButton
import com.google.android.material.textfield.TextInputEditText
import kotlinx.coroutines.launch
import org.json.JSONObject

class AuthActivity : AppCompatActivity() {

    private lateinit var etEmail: TextInputEditText
    private lateinit var etPassword: TextInputEditText
    private lateinit var btnLogin: MaterialButton
    private lateinit var tokenManager: TokenManager
    private lateinit var progressBar: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        tokenManager = TokenManager(this)

        enableEdgeToEdge()
        setContentView(R.layout.activity_auth)

        val mainView = findViewById<View>(android.R.id.content)
        ViewCompat.setOnApplyWindowInsetsListener(mainView) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        etEmail = findViewById(R.id.etEmail)
        etPassword = findViewById(R.id.etPassword)
        btnLogin = findViewById(R.id.btnLogin)
        progressBar = findViewById(R.id.progressBar)

        btnLogin.setOnClickListener {
            performLogin()
        }

        if (tokenManager.isLoggedIn()) {
            validateExistingSession()
            return
        }
    }

    private fun performLogin() {
        val email = etEmail.text.toString().trim()
        val password = etPassword.text.toString().trim()

        if (email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Please fill in all fields", Toast.LENGTH_SHORT).show()
            return
        }

        setLoading(true)

        lifecycleScope.launch {
            try {
                val loginRequest = LoginRequest(email, password)
                val response = ApiClient.getInstance().login(loginRequest)

                if (response.isSuccessful) {
                    val loginResponse = response.body()
                    val token = loginResponse?.resolvedToken()
                    if (loginResponse?.success == true && !token.isNullOrBlank()) {
                        tokenManager.saveToken(token)

                        val authedApi = ApiClient.getInstance(this@AuthActivity)
                        val meResponse = authedApi.getCurrentUser()
                        val currentUser = if (meResponse.isSuccessful) {
                            meResponse.body()?.resolvedUser()
                        } else {
                            null
                        }

                        val isTeacher = currentUser?.roles?.contains("teacher") == true
                            || loginResponse.resolvedUser()?.roles?.contains("teacher") == true

                        Toast.makeText(this@AuthActivity, "Login Successful!", Toast.LENGTH_SHORT).show()
                        goToMain(isTeacher)
                    } else {
                        Toast.makeText(this@AuthActivity, "Login failed: Invalid credentials", Toast.LENGTH_SHORT).show()
                    }
                } else {
                    // Extract server error message
                    val errorBody = response.errorBody()?.string()
                    val errorMessage = try {
                        JSONObject(errorBody ?: "").getString("message")
                    } catch (e: Exception) {
                        "Server Error: ${response.code()}"
                    }
                    Toast.makeText(this@AuthActivity, errorMessage, Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@AuthActivity, "Network Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                setLoading(false)
            }
        }
    }

    private fun validateExistingSession() {
        lifecycleScope.launch {
            try {
                val authedApi = ApiClient.getInstance(this@AuthActivity)
                val meResponse = authedApi.getCurrentUser()

                if (meResponse.isSuccessful && meResponse.body()?.resolvedUser() != null) {
                    val currentUser = meResponse.body()?.resolvedUser()
                    val isTeacher = currentUser?.roles?.contains("teacher") == true
                    goToMain(isTeacher)
                } else {
                    tokenManager.clearToken()
                    btnLogin.isEnabled = true
                }
            } catch (e: Exception) {
                tokenManager.clearToken()
                btnLogin.isEnabled = true
            }
        }
    }

    private fun setLoading(isLoading: Boolean) {
        btnLogin.isEnabled = !isLoading
        progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
    }

    private fun goToMain(isTeacher: Boolean) {
        val intent = Intent(this, MainActivity::class.java)
        intent.putExtra("IS_TEACHER", isTeacher)
        startActivity(intent)
        finish()
    }
}
