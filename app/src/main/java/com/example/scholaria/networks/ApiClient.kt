package com.example.scholaria.networks

import android.content.Context
import com.example.scholaria.BuildConfig
import com.example.scholaria.utils.TokenManager
import okhttp3.CookieJar
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    @JvmStatic
    fun getInstance(): ApiService {
        return createService(ApiConfig.defaultBaseUrl(), null)
    }

    @JvmStatic
    fun getInstance(token: String): ApiService {
        return createService(ApiConfig.defaultBaseUrl(), token)
    }

    @JvmStatic
    fun getInstance(context: Context): ApiService {
        val token = TokenManager(context.applicationContext).getToken()
        return createService(ApiConfig.defaultBaseUrl(), token)
    }

    @JvmStatic
    fun getInstance(context: Context, token: String?): ApiService {
        val resolvedToken = token ?: TokenManager(context.applicationContext).getToken()
        return createService(ApiConfig.defaultBaseUrl(), resolvedToken)
    }

    @JvmStatic
    fun getInstance(baseUrl: String, token: String? = null): ApiService {
        return createService(baseUrl, token)
    }

    private fun createService(baseUrl: String, token: String?): ApiService {
        val clientBuilder = OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .cookieJar(CookieJar.NO_COOKIES)

        clientBuilder.addInterceptor { chain ->
            val requestBuilder = chain.request().newBuilder()
                .header("Accept", "application/json")
                .header("X-Requested-With", "XMLHttpRequest")

            if (token != null) {
                requestBuilder.header("Authorization", "Bearer $token")
            }

            chain.proceed(requestBuilder.build())
        }

        val logging = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BODY
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
        }
        clientBuilder.addInterceptor(logging)

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(clientBuilder.build())
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ApiService::class.java)
    }
}
