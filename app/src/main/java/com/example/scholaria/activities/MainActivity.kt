package com.example.scholaria.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.Toast;
import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.graphics.Insets;
import androidx.core.view.GravityCompat;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.drawerlayout.widget.DrawerLayout;
import androidx.viewpager2.widget.ViewPager2;
import com.example.scholaria.R;
import com.example.scholaria.adapters.MainPagerAdapter;
import com.google.android.material.navigation.NavigationView;

public class MainActivity extends AppCompatActivity {

    private DrawerLayout drawerLayout;
    private NavigationView navigationView;
    private ViewPager2 viewPager;
    private View navIndicator;
    private ImageView[] navIcons;
    private int activeColor, inactiveColor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        View mainContent = findViewById(R.id.mainContent);
        ViewCompat.setOnApplyWindowInsetsListener(mainContent, (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        activeColor = ContextCompat.getColor(this, R.color.dash_nav_selected);
        inactiveColor = ContextCompat.getColor(this, R.color.dash_nav_unselected);

        initViews();
        setupViewPager();
        setupNavClicks();
        setupDrawer();
        setupSearch();
    }

    private void initViews() {
        drawerLayout = findViewById(R.id.drawerLayout);
        navigationView = findViewById(R.id.navView);
        viewPager = findViewById(R.id.mainViewPager);
        navIndicator = findViewById(R.id.navIndicator);

        navIcons = new ImageView[]{
                findViewById(R.id.navHome),
                findViewById(R.id.navCourses),
                findViewById(R.id.navTasks),
                findViewById(R.id.navMessages),
                findViewById(R.id.navNotifications),
                findViewById(R.id.navProfile)
        };
    }

    private void setupSearch() {
        ImageView ivSearch = findViewById(R.id.ivSearch);
        ivSearch.setOnClickListener(v -> {
            Intent intent = new Intent(MainActivity.this, SearchActivity.class);
            startActivity(intent);
        });
    }

    private void setupDrawer() {
        ImageView ivMenu = findViewById(R.id.ivMenu);
        ivMenu.setOnClickListener(v -> drawerLayout.openDrawer(GravityCompat.START));

        navigationView.setNavigationItemSelectedListener(item -> {
            int id = item.getItemId();

            if (id == R.id.nav_files) {
                Toast.makeText(this, "Opening Files...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_activities) {
                Toast.makeText(this, "Opening Activities...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_grades) {
                Toast.makeText(this, "Opening Grades...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_calendar) {
                Toast.makeText(this, "Opening Calendar...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_settings) {
                Toast.makeText(this, "Opening Settings...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_help) {
                Toast.makeText(this, "Opening Help...", Toast.LENGTH_SHORT).show();
            } else if (id == R.id.nav_logout) {
                Toast.makeText(this, "Logging out...", Toast.LENGTH_SHORT).show();
                finish(); // Example action
            }

            drawerLayout.closeDrawer(GravityCompat.START);
            return true;
        });
    }

    private void setupViewPager() {
        viewPager.setAdapter(new MainPagerAdapter(this));
        viewPager.setUserInputEnabled(true);

        viewPager.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageScrolled(int position, float positionOffset, int positionOffsetPixels) {
                updateIndicatorPosition(position, positionOffset);
            }

            @Override
            public void onPageSelected(int position) {
                updateNavIcons(position);
            }
        });
    }

    private void setupNavClicks() {
        for (int i = 0; i < navIcons.length; i++) {
            final int index = i;
            navIcons[i].setOnClickListener(v -> viewPager.setCurrentItem(index));
        }
    }

    private void updateIndicatorPosition(int position, float positionOffset) {
        int tabWidth = navIcons[0].getWidth();
        if (tabWidth == 0) return;

        float translationX = (position + positionOffset) * tabWidth;
        navIndicator.getLayoutParams().width = tabWidth;
        navIndicator.setTranslationX(translationX + navIcons[0].getLeft());
        navIndicator.requestLayout();
    }

    private void updateNavIcons(int activeIndex) {
        for (int i = 0; i < navIcons.length; i++) {
            navIcons[i].setColorFilter(i == activeIndex ? activeColor : inactiveColor);
            navIcons[i].setBackgroundResource(0);
        }
    }

    @Override
    public void onBackPressed() {
        if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
            drawerLayout.closeDrawer(GravityCompat.START);
        } else {
            super.onBackPressed();
        }
    }
}
