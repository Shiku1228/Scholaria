package com.example.scholaria.activities

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.ImageView
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.GravityCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.drawerlayout.widget.DrawerLayout
import androidx.lifecycle.lifecycleScope
import androidx.viewpager2.widget.ViewPager2
import com.example.scholaria.R
import com.example.scholaria.adapters.MainPagerAdapter
import com.example.scholaria.activities.FeatureHubActivity
import com.example.scholaria.activities.FeatureListActivity
import com.example.scholaria.networks.ApiClient
import com.example.scholaria.utils.TokenManager
import com.example.scholaria.utils.FeatureRoutes
import com.google.android.material.navigation.NavigationView
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var drawerLayout: DrawerLayout
    private lateinit var navigationView: NavigationView
    private lateinit var viewPager: ViewPager2
    private lateinit var navIndicator: View
    private lateinit var navIcons: MutableList<ImageView>
    private var activeColor: Int = 0
    private var inactiveColor: Int = 0
    private var isTeacher: Boolean = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContentView(R.layout.activity_main)

        isTeacher = intent.getBooleanExtra("IS_TEACHER", false)

        val mainContent = findViewById<View>(R.id.mainContent)
        ViewCompat.setOnApplyWindowInsetsListener(mainContent) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        activeColor = ContextCompat.getColor(this, R.color.dash_nav_selected)
        inactiveColor = ContextCompat.getColor(this, R.color.dash_nav_unselected)

        initViews()
        setupViewPager()
        setupNavClicks()
        setupDrawer()
        setupSearch()

        val tokenManager = TokenManager(this)

        if (!tokenManager.isLoggedIn()) {
            Toast.makeText(this, "Session expired. Please log in again.", Toast.LENGTH_SHORT).show()
            redirectToAuth()
            return
        }

        validateSession()
    }

    private fun initViews() {
        drawerLayout = findViewById(R.id.drawerLayout)
        navigationView = findViewById(R.id.navView)
        viewPager = findViewById(R.id.mainViewPager)
        navIndicator = findViewById(R.id.navIndicator)
        navigationView.menu.clear()
        navigationView.inflateMenu(if (isTeacher) R.menu.drawer_menu_teacher else R.menu.drawer_menu)

        navIcons = mutableListOf()
        navIcons.add(findViewById(R.id.navHome))
        navIcons.add(findViewById(R.id.navCourses))

        val navStudents = findViewById<ImageView>(R.id.navStudents)
        if (isTeacher) {
            navStudents.visibility = View.VISIBLE
            navIcons.add(navStudents)
        } else {
            navStudents.visibility = View.GONE
        }

        navIcons.add(findViewById(R.id.navTasks))
        navIcons.add(findViewById(R.id.navMessages))
        navIcons.add(findViewById(R.id.navNotifications))
        navIcons.add(findViewById(R.id.navProfile))
    }

    private fun setupSearch() {
        val ivSearch = findViewById<ImageView>(R.id.ivSearch)
        ivSearch.setOnClickListener {
            val intent = Intent(this@MainActivity, SearchActivity::class.java)
            startActivity(intent)
        }
    }

    private fun setupDrawer() {
        val ivMenu = findViewById<ImageView>(R.id.ivMenu)
        ivMenu.setOnClickListener { drawerLayout.openDrawer(GravityCompat.START) }

        navigationView.setNavigationItemSelectedListener { item ->
            val id = item.itemId
            if (id == R.id.nav_logout) {
                logout()
            } else if (id == R.id.nav_files || id == R.id.nav_teacher_courses) {
                openFeatureList("Courses", "Active and archived classes", FeatureRoutes.ROUTE_COURSES)
            } else if (id == R.id.nav_activities || id == R.id.nav_teacher_assignments) {
                openFeatureList("Assignments", "Tasks and submissions", FeatureRoutes.ROUTE_ASSIGNMENTS)
            } else if (id == R.id.nav_grades) {
                openFeatureList("Grades", "Marks and released results", FeatureRoutes.ROUTE_GRADES)
            } else if (id == R.id.nav_calendar) {
                openFeatureList("Calendar", "Important academic dates", FeatureRoutes.ROUTE_CALENDAR)
            } else if (id == R.id.nav_teacher_students) {
                openFeatureList("Students", "Roster and progress", FeatureRoutes.ROUTE_STUDENTS)
            } else if (id == R.id.nav_teacher_enrollments) {
                openFeatureList("Enrollments", "Class membership records", FeatureRoutes.ROUTE_ENROLLMENTS)
            } else if (id == R.id.nav_teacher_announcements) {
                openFeatureList("Announcements", "Broadcast class updates", FeatureRoutes.ROUTE_ANNOUNCEMENTS)
            } else if (id == R.id.nav_teacher_messages) {
                openFeatureList("Messages", "Instructor and student conversations", FeatureRoutes.ROUTE_MESSAGES)
            } else if (id == R.id.nav_settings) {
                openFeatureList("Settings", "App and account preferences", FeatureRoutes.ROUTE_SETTINGS)
            } else if (id == R.id.nav_help) {
                openHub()
            } else {
                Toast.makeText(this, "Opening " + item.title, Toast.LENGTH_SHORT).show()
            }
            drawerLayout.closeDrawer(GravityCompat.START)
            true
        }
    }

    fun logout() {
        Toast.makeText(this, "Logging out...", Toast.LENGTH_SHORT).show()

        lifecycleScope.launch {
            try {
                val api = ApiClient.getInstance(this@MainActivity)
                api.logout()
            } catch (_: Exception) {
                // Clear local session even when the server call fails.
            } finally {
                TokenManager(this@MainActivity).clearToken()
                redirectToAuth()
            }
        }
    }

    private fun validateSession() {
        lifecycleScope.launch {
            try {
                val api = ApiClient.getInstance(this@MainActivity)
                val response = api.getCurrentUser()

                if (!response.isSuccessful || response.body()?.resolvedUser() == null) {
                    TokenManager(this@MainActivity).clearToken()
                    redirectToAuth()
                }
            } catch (_: Exception) {
                TokenManager(this@MainActivity).clearToken()
                redirectToAuth()
            }
        }
    }

    private fun redirectToAuth() {
        val intent = Intent(this@MainActivity, AuthActivity::class.java)
        intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        startActivity(intent)
        finish()
    }

    private fun openHub() {
        val intent = Intent(this, FeatureHubActivity::class.java)
        intent.putExtra(FeatureRoutes.EXTRA_ROLE, if (isTeacher) FeatureRoutes.ROLE_TEACHER else FeatureRoutes.ROLE_STUDENT)
        startActivity(intent)
    }

    private fun openFeatureList(title: String, subtitle: String, route: String) {
        val intent = Intent(this, FeatureListActivity::class.java)
        intent.putExtra(FeatureRoutes.EXTRA_TITLE, title)
        intent.putExtra(FeatureRoutes.EXTRA_SUBTITLE, subtitle)
        intent.putExtra(FeatureRoutes.EXTRA_ROUTE, route)
        intent.putExtra(FeatureRoutes.EXTRA_ROLE, if (isTeacher) FeatureRoutes.ROLE_TEACHER else FeatureRoutes.ROLE_STUDENT)
        startActivity(intent)
    }

    private fun setupViewPager() {
        viewPager.adapter = MainPagerAdapter(this, isTeacher)
        viewPager.isUserInputEnabled = true

        viewPager.registerOnPageChangeCallback(object : ViewPager2.OnPageChangeCallback() {
            override fun onPageScrolled(position: Int, positionOffset: Float, positionOffsetPixels: Int) {
                updateIndicatorPosition(position, positionOffset)
            }

            override fun onPageSelected(position: Int) {
                updateNavIcons(position)
            }
        })
    }

    private fun setupNavClicks() {
        for (i in navIcons.indices) {
            val index = i
            navIcons[i].setOnClickListener { viewPager.currentItem = index }
        }
    }

    private fun updateIndicatorPosition(position: Int, positionOffset: Float) {
        if (navIcons.isEmpty() || position >= navIcons.size) return

        val tabWidth = navIcons[position].width
        if (tabWidth == 0) return

        val translationX = (position + positionOffset) * tabWidth

        // Adjust translation based on start of first icon
        val params = navIndicator.layoutParams
        params.width = tabWidth
        navIndicator.layoutParams = params
        navIndicator.translationX = translationX + navIcons[0].left
        navIndicator.requestLayout()
    }

    private fun updateNavIcons(activeIndex: Int) {
        for (i in navIcons.indices) {
            navIcons[i].setColorFilter(if (i == activeIndex) activeColor else inactiveColor)
        }
    }

    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
            drawerLayout.closeDrawer(GravityCompat.START)
        } else {
            super.onBackPressed()
        }
    }
}
