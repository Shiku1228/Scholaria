package com.example.scholaria.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.activities.FeatureHubActivity;
import com.example.scholaria.activities.FeatureListActivity;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.adapters.FeatureShortcutAdapter;
import com.example.scholaria.adapters.EventAdapter;
import com.example.scholaria.adapters.SubjectAdapter;
import com.example.scholaria.models.Assignment;
import com.example.scholaria.models.Event;
import com.example.scholaria.models.FeatureShortcut;
import com.example.scholaria.models.Subject;
import com.example.scholaria.utils.FeatureRoutes;
import android.content.Intent;
import java.util.ArrayList;
import java.util.List;

public class TeacherDashboardFragment extends Fragment {

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_teacher_dashboard, container, false);

        setupTeacherClasses(view);
        setupUpcomingClasses(view);
        setupRecentSubmissions(view);
        setupFeatureShortcuts(view);
        setupClickListeners(view);

        return view;
    }

    private void setupFeatureShortcuts(View v) {
        RecyclerView rv = v.findViewById(R.id.rvTeacherFeatureShortcuts);
        List<FeatureShortcut> items = new ArrayList<>();
        items.add(new FeatureShortcut("Courses", "Manage sections", R.drawable.ic_nav_courses, FeatureRoutes.ROUTE_COURSES, "Core"));
        items.add(new FeatureShortcut("Students", "Roster and progress", R.drawable.ic_nav_students, FeatureRoutes.ROUTE_STUDENTS, "People"));
        items.add(new FeatureShortcut("Assignments", "Create and grade tasks", R.drawable.ic_nav_tasks, FeatureRoutes.ROUTE_ASSIGNMENTS, "Work"));
        items.add(new FeatureShortcut("Exams", "Publish and review", R.drawable.ic_security_lock, FeatureRoutes.ROUTE_EXAMS, "Assess"));
        items.add(new FeatureShortcut("Quizzes", "Question banks", R.drawable.ic_help_outline, FeatureRoutes.ROUTE_QUIZZES, "Assess"));
        items.add(new FeatureShortcut("Announcements", "Post updates", R.drawable.ic_open_envelope, FeatureRoutes.ROUTE_ANNOUNCEMENTS, "News"));
        items.add(new FeatureShortcut("Enrollments", "Manage class lists", R.drawable.ic_nav_students, FeatureRoutes.ROUTE_ENROLLMENTS, "People"));
        items.add(new FeatureShortcut("Office Hours", "Support students", R.drawable.ic_history, FeatureRoutes.ROUTE_OFFICE_HOURS, "Support"));

        rv.setLayoutManager(new GridLayoutManager(getContext(), 2));
        rv.setAdapter(new FeatureShortcutAdapter(items, shortcut -> openFeature(shortcut)));

        v.findViewById(R.id.tvSeeAllTeacherFeatures).setOnClickListener(view -> {
            Intent intent = new Intent(getContext(), FeatureHubActivity.class);
            intent.putExtra(FeatureRoutes.EXTRA_ROLE, FeatureRoutes.ROLE_TEACHER);
            startActivity(intent);
        });
    }

    private void setupTeacherClasses(View v) {
        RecyclerView rv = v.findViewById(R.id.rvTeacherClasses);
        List<Subject> list = new ArrayList<>();
        list.add(new Subject("Mobile Dev", "IT311", "Section A"));
        list.add(new Subject("Database Sys", "IT212", "Section B"));
        list.add(new Subject("Networking", "IT314", "Section C"));

        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new SubjectAdapter(list));
    }

    private void setupUpcomingClasses(View v) {
        RecyclerView rv = v.findViewById(R.id.rvUpcomingClasses);
        List<Event> list = new ArrayList<>();
        list.add(new Event("Mobile Dev Class", "TODAY", "10:00 AM - 12:00 PM"));
        list.add(new Event("Faculty Meeting", "TODAY", "02:00 PM - 03:00 PM"));
        list.add(new Event("Database Quiz", "TOMORROW", "08:00 AM - 09:00 AM"));

        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new EventAdapter(list));
    }

    private void setupRecentSubmissions(View v) {
        RecyclerView rv = v.findViewById(R.id.rvRecentSubmissions);
        List<Assignment> list = new ArrayList<>();
        list.add(new Assignment("John Doe", "Mobile Dev", "Submitted: 2 mins ago"));
        list.add(new Assignment("Jane Smith", "Database Sys", "Submitted: 1 hour ago"));
        list.add(new Assignment("Renz Latangga", "Networking", "Submitted: 3 hours ago"));

        rv.setLayoutManager(new LinearLayoutManager(getContext()));
        rv.setAdapter(new AssignmentAdapter(list));
    }

    private void setupClickListeners(View v) {
        v.findViewById(R.id.tvAddClass).setOnClickListener(view ->
            Toast.makeText(getContext(), "Create Class clicked", Toast.LENGTH_SHORT).show());

        v.findViewById(R.id.tvViewCalendarTeacher).setOnClickListener(view ->
            Toast.makeText(getContext(), "Opening Calendar...", Toast.LENGTH_SHORT).show());

        v.findViewById(R.id.btnAnnouncement).setOnClickListener(view ->
            Toast.makeText(getContext(), "Post Announcement clicked", Toast.LENGTH_SHORT).show());

        v.findViewById(R.id.btnUploadFiles).setOnClickListener(view ->
            Toast.makeText(getContext(), "Upload Files clicked", Toast.LENGTH_SHORT).show());
    }

    private void openFeature(FeatureShortcut shortcut) {
        Intent intent = new Intent(getContext(), FeatureListActivity.class);
        intent.putExtra(FeatureRoutes.EXTRA_TITLE, shortcut.getTitle());
        intent.putExtra(FeatureRoutes.EXTRA_SUBTITLE, shortcut.getSubtitle());
        intent.putExtra(FeatureRoutes.EXTRA_ROUTE, shortcut.getRouteKey());
        intent.putExtra(FeatureRoutes.EXTRA_ROLE, FeatureRoutes.ROLE_TEACHER);
        startActivity(intent);
    }
}
