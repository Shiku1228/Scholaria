package com.example.scholaria.adapters;

import androidx.annotation.NonNull;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;
import com.example.scholaria.fragments.AssignmentFragment;
import com.example.scholaria.fragments.CoursesFragment;
import com.example.scholaria.fragments.DashboardFragment;
import com.example.scholaria.fragments.MessagesFragment;
import com.example.scholaria.fragments.NotificationsFragment;
import com.example.scholaria.fragments.ProfileFragment;
import com.example.scholaria.fragments.StudentsFragment;
import com.example.scholaria.fragments.TeacherDashboardFragment;

public class MainPagerAdapter extends FragmentStateAdapter {

    private boolean isTeacher;

    public MainPagerAdapter(@NonNull FragmentActivity fragmentActivity, boolean isTeacher) {
        super(fragmentActivity);
        this.isTeacher = isTeacher;
    }

    @NonNull
    @Override
    public Fragment createFragment(int position) {
        if (isTeacher) {
            switch (position) {
                case 0: return new TeacherDashboardFragment();
                case 1: return new CoursesFragment();
                case 2: return new StudentsFragment();
                case 3: return new AssignmentFragment();
                case 4: return new MessagesFragment();
                case 5: return new NotificationsFragment();
                case 6: return new ProfileFragment();
                default: return new TeacherDashboardFragment();
            }
        } else {
            switch (position) {
                case 0: return new DashboardFragment();
                case 1: return new CoursesFragment();
                case 2: return new AssignmentFragment();
                case 3: return new MessagesFragment();
                case 4: return new NotificationsFragment();
                case 5: return new ProfileFragment();
                default: return new DashboardFragment();
            }
        }
    }

    @Override
    public int getItemCount() {
        return isTeacher ? 7 : 6;
    }
}
