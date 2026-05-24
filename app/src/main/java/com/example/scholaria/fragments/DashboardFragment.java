package com.example.scholaria.fragments;

import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.viewpager2.widget.ViewPager2;

import com.example.scholaria.R;
import com.example.scholaria.activities.FeatureHubActivity;
import com.example.scholaria.activities.FeatureListActivity;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.adapters.EventAdapter;
import com.example.scholaria.adapters.FeatureShortcutAdapter;
import com.example.scholaria.adapters.SubjectAdapter;
import com.example.scholaria.models.Assignment;
import com.example.scholaria.models.Event;
import com.example.scholaria.models.FeatureShortcut;
import com.example.scholaria.models.Subject;
import com.example.scholaria.networks.ApiClient;
import com.example.scholaria.networks.StudentApiResponse;
import com.example.scholaria.networks.StudentDashboardDataDto;
import com.example.scholaria.networks.StudentCourseDto;
import com.example.scholaria.networks.StudentRecentAnnouncementDto;
import com.example.scholaria.networks.StudentUpcomingAssignmentDto;
import com.example.scholaria.utils.FeatureRoutes;
import com.google.android.material.snackbar.Snackbar;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class DashboardFragment extends Fragment {

    private final List<Subject> subjects = new ArrayList<>();
    private final List<Assignment> assignments = new ArrayList<>();
    private final List<Event> events = new ArrayList<>();
    private SubjectAdapter subjectAdapter;
    private AssignmentAdapter assignmentAdapter;
    private EventAdapter eventAdapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_dashboard, container, false);

        setupSubjects(view);
        setupAssignments(view);
        setupEvents(view);
        setupFeatureShortcuts(view);
        setupNavigation(view);
        loadDashboardData();

        return view;
    }

    private void setupFeatureShortcuts(View v) {
        RecyclerView rv = v.findViewById(R.id.rvFeatureShortcuts);
        List<FeatureShortcut> items = new ArrayList<>();
        items.add(new FeatureShortcut("Courses", "View active classes", R.drawable.ic_nav_courses, FeatureRoutes.ROUTE_COURSES, "Core"));
        items.add(new FeatureShortcut("Assignments", "Track due work", R.drawable.ic_nav_tasks, FeatureRoutes.ROUTE_ASSIGNMENTS, "Work"));
        items.add(new FeatureShortcut("Exams", "Upcoming and missed exams", R.drawable.ic_security_lock, FeatureRoutes.ROUTE_EXAMS, "Assess"));
        items.add(new FeatureShortcut("Quizzes", "Practice and results", R.drawable.ic_help_outline, FeatureRoutes.ROUTE_QUIZZES, "Assess"));
        items.add(new FeatureShortcut("Grades", "See your marks", R.drawable.ic_nav_grades, FeatureRoutes.ROUTE_GRADES, "Results"));
        items.add(new FeatureShortcut("Office Hours", "Book support slots", R.drawable.ic_history, FeatureRoutes.ROUTE_OFFICE_HOURS, "Support"));

        rv.setLayoutManager(new GridLayoutManager(getContext(), 2));
        rv.setAdapter(new FeatureShortcutAdapter(items, shortcut -> openFeature(shortcut)));

        TextView tvSeeAllFeatures = v.findViewById(R.id.tvSeeAllFeatures);
        tvSeeAllFeatures.setOnClickListener(view -> {
            Intent intent = new Intent(getContext(), FeatureHubActivity.class);
            intent.putExtra(FeatureRoutes.EXTRA_ROLE, FeatureRoutes.ROLE_STUDENT);
            startActivity(intent);
        });
    }

    private void setupNavigation(View v) {
        TextView tvSeeAllSubjects = v.findViewById(R.id.tvSeeAllSubjects);
        TextView tvSeeAllAssignments = v.findViewById(R.id.tvSeeAllAssignments);
        TextView tvViewCalendar = v.findViewById(R.id.tvViewCalendar);

        ViewPager2 viewPager = getActivity() != null ? getActivity().findViewById(R.id.mainViewPager) : null;

        if (viewPager != null) {
            tvSeeAllSubjects.setOnClickListener(view -> viewPager.setCurrentItem(1));
            tvSeeAllAssignments.setOnClickListener(view -> viewPager.setCurrentItem(2));
            tvViewCalendar.setOnClickListener(view -> {
                Intent intent = new Intent(getContext(), FeatureListActivity.class);
                intent.putExtra(FeatureRoutes.EXTRA_TITLE, "Calendar");
                intent.putExtra(FeatureRoutes.EXTRA_SUBTITLE, "Important dates and upcoming events");
                intent.putExtra(FeatureRoutes.EXTRA_ROUTE, FeatureRoutes.ROUTE_CALENDAR);
                intent.putExtra(FeatureRoutes.EXTRA_ROLE, FeatureRoutes.ROLE_STUDENT);
                startActivity(intent);
            });
        }
    }

    private void openFeature(FeatureShortcut shortcut) {
        Intent intent = new Intent(getContext(), FeatureListActivity.class);
        intent.putExtra(FeatureRoutes.EXTRA_TITLE, shortcut.getTitle());
        intent.putExtra(FeatureRoutes.EXTRA_SUBTITLE, shortcut.getSubtitle());
        intent.putExtra(FeatureRoutes.EXTRA_ROUTE, shortcut.getRouteKey());
        intent.putExtra(FeatureRoutes.EXTRA_ROLE, FeatureRoutes.ROLE_STUDENT);
        startActivity(intent);
    }

    private void setupSubjects(View v) {
        RecyclerView rv = v.findViewById(R.id.rvSubjects);
        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        subjectAdapter = new SubjectAdapter(subjects);
        rv.setAdapter(subjectAdapter);
    }

    private void setupAssignments(View v) {
        RecyclerView rv = v.findViewById(R.id.rvAssignments);
        rv.setLayoutManager(new LinearLayoutManager(getContext()));
        assignmentAdapter = new AssignmentAdapter(assignments);
        rv.setAdapter(assignmentAdapter);
    }

    private void setupEvents(View v) {
        RecyclerView rv = v.findViewById(R.id.rvEvents);
        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        eventAdapter = new EventAdapter(events);
        rv.setAdapter(eventAdapter);
    }

    private void loadDashboardData() {
        if (getContext() == null) {
            return;
        }

        ApiClient.getInstance(requireContext())
                .getStudentDashboardCall()
                .enqueue(new Callback<StudentApiResponse<StudentDashboardDataDto>>() {
                    @Override
                    public void onResponse(@NonNull Call<StudentApiResponse<StudentDashboardDataDto>> call, @NonNull Response<StudentApiResponse<StudentDashboardDataDto>> response) {
                        if (!isAdded()) {
                            return;
                        }

                        if (!response.isSuccessful() || response.body() == null || response.body().getData() == null) {
                            showDashboardError("Unable to load dashboard data.");
                            return;
                        }

                        bindDashboard(response.body().getData());
                    }

                    @Override
                    public void onFailure(@NonNull Call<StudentApiResponse<StudentDashboardDataDto>> call, @NonNull Throwable t) {
                        if (isAdded()) {
                            showDashboardError("Dashboard sync failed.");
                        }
                    }
                });
    }

    private void bindDashboard(StudentDashboardDataDto data) {
        subjects.clear();
        for (StudentCourseDto dto : data.getCourses()) {
            subjects.add(new Subject(
                    String.valueOf(dto.getCourseId()),
                    safe(dto.getCourseName(), "Course"),
                    safe(dto.getCourseNumber(), "Code"),
                    safe(dto.getTeacherName(), safe(dto.getSchoolYear(), "Class"))
            ));
        }

        assignments.clear();
        for (StudentUpcomingAssignmentDto dto : data.getUpcomingAssignments()) {
            assignments.add(new Assignment(
                    safe(dto.getAssignmentTitle(), "Assignment"),
                    safe(dto.getCourseName(), "Course"),
                    formatDueLabel(dto.getDueDate())
            ));
        }

        events.clear();
        for (StudentUpcomingAssignmentDto dto : data.getUpcomingAssignments()) {
            events.add(new Event(
                    safe(dto.getAssignmentTitle(), "Assignment"),
                    formatShortDate(dto.getDueDate()),
                    safe(dto.getCourseName(), "Course")
            ));
        }
        for (StudentRecentAnnouncementDto dto : data.getRecentAnnouncements()) {
            events.add(new Event(
                    safe(dto.getTitle(), "Announcement"),
                    formatShortDate(dto.getCreatedAt()),
                    safe(dto.getCourseName(), "Course")
            ));
        }

        notifyDashboardAdapters();
    }

    private void notifyDashboardAdapters() {
        if (subjectAdapter != null) subjectAdapter.notifyDataSetChanged();
        if (assignmentAdapter != null) assignmentAdapter.notifyDataSetChanged();
        if (eventAdapter != null) eventAdapter.notifyDataSetChanged();
    }

    private void showDashboardError(String message) {
        if (getView() != null) {
            Snackbar.make(getView(), message, Snackbar.LENGTH_SHORT).show();
        }
    }

    private String safe(String value, String fallback) {
        return value == null || value.trim().isEmpty() ? fallback : value;
    }

    private String formatDueLabel(String value) {
        return value == null || value.isEmpty() ? "Pending" : value;
    }

    private String formatShortDate(String value) {
        if (value == null || value.trim().isEmpty()) {
            return "TBA";
        }
        try {
            Date date = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).parse(value);
            if (date != null) {
                return new SimpleDateFormat("MMM dd", Locale.US).format(date).toUpperCase(Locale.US);
            }
        } catch (ParseException ignored) {
        }
        return value;
    }

}
