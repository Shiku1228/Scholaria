package com.example.scholaria

import android.app.Application

class ScholariaApp : Application() {
    override fun onCreate() {
        super.onCreate()
        instance = this
    }

    companion object {
        lateinit var instance: ScholariaApp
            private set
    }
}
