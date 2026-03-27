package com.example.scholaria.adapters;

import androidx.annotation.NonNull;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;
import com.example.scholaria.fragments.AssignmentFragment;
import com.example.scholaria.fragments.CoursesFragment;
import com.example.scholaria.fragments.DashboardFragment;

public class MainPagerAdapter extends FragmentStateAdapter {

    public MainPagerAdapter(@NonNull FragmentActivity fragmentActivity) {
        super(fragmentActivity);
    }

    @NonNull
    @Override
    public Fragment createFragment(int position) {
        switch (position) {
            case 0: return new DashboardFragment();
            case 1: return new CoursesFragment();
            case 2: return new AssignmentFragment();
            // Add other fragments here as we create them
            default: return new DashboardFragment();
        }
    }

    @Override
    public int getItemCount() {
        return 6; // Total tabs: Home, Courses, Tasks, Messages, Notifications, Profile
    }
}
