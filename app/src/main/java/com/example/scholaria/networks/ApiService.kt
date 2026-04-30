package com.example.scholaria.networks

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface ApiService {
    @GET("test")
    suspend fun testConnection(): Response<TestResponse>

    @POST("login")
    suspend fun login(@Body loginRequest: LoginRequest): Response<LoginResponse>

    @POST("login-test")
    suspend fun loginTest(@Body loginRequest: LoginRequest): Response<LoginResponse>

    @GET("me")
    suspend fun getCurrentUser(): Response<UserResponse>
}

data class TestResponse(
    val status: String,
    val message: String,
    val timestamp: String
)

data class LoginRequest(val email: String, val password: String)

data class LoginResponse(
    val success: Boolean,
    val token: String?,
    val user: User?
)

data class UserResponse(
    val success: Boolean,
    val user: User
)

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val roles: List<String> = emptyList()
)
