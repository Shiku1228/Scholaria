package com.example.scholaria.activities;

import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.viewpager2.widget.ViewPager2;
import com.example.scholaria.R;
import com.example.scholaria.adapters.MainPagerAdapter;

public class MainActivity extends AppCompatActivity {

    private ViewPager2 viewPager;
    private View navIndicator;
    private ImageView[] navIcons;
    private int activeColor, inactiveColor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(android.R.id.content), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        activeColor = ContextCompat.getColor(this, R.color.dash_nav_selected);
        inactiveColor = ContextCompat.getColor(this, R.color.dash_nav_unselected);

        initViews();
        setupViewPager();
        setupNavClicks();
    }

    private void initViews() {
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

    private void setupViewPager() {
        viewPager.setAdapter(new MainPagerAdapter(this));

        // Disable swiping if you want it strictly button-based,
        // but swiping makes it feel more "modern" like Facebook.
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
        if (tabWidth == 0) return; // Wait for layout

        // Calculate the translation based on tab width and scroll offset
        float translationX = (position + positionOffset) * tabWidth;

        navIndicator.getLayoutParams().width = tabWidth;
        navIndicator.setTranslationX(translationX + navIcons[0].getLeft());
        navIndicator.requestLayout();
    }

    private void updateNavIcons(int activeIndex) {
        for (int i = 0; i < navIcons.length; i++) {
            navIcons[i].setColorFilter(i == activeIndex ? activeColor : inactiveColor);
            // Optionally remove background from all and add to active
            navIcons[i].setBackgroundResource(0);
        }
        // If you want the subtle teal background on active tab:
        // navIcons[activeIndex].setBackgroundResource(R.drawable.sub_nav_selected_bg);
    }
}
