package com.example.scholaria.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.adapters.CourseAdapter;
import com.example.scholaria.models.Course;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.chip.ChipGroup;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

public class CoursesFragment extends Fragment {

    private RecyclerView rvMyCourses;
    private CourseAdapter adapter;
    private MaterialButton btnCycleView;
    private ChipGroup chipGroupFilter;
    private int currentViewMode = CourseAdapter.VIEW_TYPE_LARGE;

    private List<Course> allCoursesList = new ArrayList<>();
    private List<Course> filteredList = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_courses, container, false);

        rvMyCourses = view.findViewById(R.id.rvMyCourses);
        btnCycleView = view.findViewById(R.id.btnCycleView);
        chipGroupFilter = view.findViewById(R.id.chipGroupFilter);

        initData();
        setupRecyclerView();
        setupViewSwitchers();
        setupFilterChips();

        // Initial Filter
        filterCourses(R.id.chipCurrent);

        return view;
    }

    private void initData() {
        allCoursesList.clear();
        // Current Semester
        allCoursesList.add(new Course("Application Development", "CC106", "1st Semester", "2026-2027", 75, "12/15 assignments"));
        allCoursesList.add(new Course("Web Systems", "WS067", "1st Semester", "2026-2027", 40, "5/12 assignments"));
        allCoursesList.add(new Course("Data Structures", "CS201", "1st Semester", "2026-2027", 10, "1/10 assignments"));

        // Last Semester
        allCoursesList.add(new Course("Computer Programming 2", "CC102", "2nd Semester", "2025-2026", 100, "20/20 assignments"));
        allCoursesList.add(new Course("Discrete Mathematics", "MATH103", "2nd Semester", "2025-2026", 100, "15/15 assignments"));
        allCoursesList.add(new Course("Networking 1", "IT201", "2nd Semester", "2025-2026", 100, "10/10 assignments"));
    }

    private void setupRecyclerView() {
        filteredList = new ArrayList<>(allCoursesList);
        adapter = new CourseAdapter(filteredList);
        rvMyCourses.setLayoutManager(new LinearLayoutManager(getContext()));
        rvMyCourses.setAdapter(adapter);
    }

    private void setupViewSwitchers() {
        btnCycleView.setOnClickListener(v -> {
            if (currentViewMode == CourseAdapter.VIEW_TYPE_LARGE) {
                updateViewType(CourseAdapter.VIEW_TYPE_SMALL);
            } else if (currentViewMode == CourseAdapter.VIEW_TYPE_SMALL) {
                updateViewType(CourseAdapter.VIEW_TYPE_LIST);
            } else {
                updateViewType(CourseAdapter.VIEW_TYPE_LARGE);
            }
        });
    }

    private void setupFilterChips() {
        chipGroupFilter.setOnCheckedChangeListener((group, checkedId) -> {
            filterCourses(checkedId);
        });
    }

    private void filterCourses(int checkedId) {
        filteredList.clear();
        if (checkedId == R.id.chipCurrent) {
            // Filter for 1st Semester 2026-2027 (mocking current)
            for (Course c : allCoursesList) {
                if (c.getSemester().equals("1st Semester") && c.getYear().equals("2026-2027")) {
                    filteredList.add(c);
                }
            }
        } else if (checkedId == R.id.chipLast) {
            // Filter for 2nd Semester 2025-2026 (mocking last)
            for (Course c : allCoursesList) {
                if (c.getSemester().equals("2nd Semester") && c.getYear().equals("2025-2026")) {
                    filteredList.add(c);
                }
            }
        } else {
            // View All
            filteredList.addAll(allCoursesList);
        }

        adapter.notifyDataSetChanged();
    }

    private void updateViewType(int type) {
        currentViewMode = type;
        adapter.setViewType(type);

        if (type == CourseAdapter.VIEW_TYPE_SMALL) {
            rvMyCourses.setLayoutManager(new GridLayoutManager(getContext(), 2));
            btnCycleView.setIconResource(R.drawable.ic_view_mode_small);
        } else if (type == CourseAdapter.VIEW_TYPE_LIST) {
            rvMyCourses.setLayoutManager(new LinearLayoutManager(getContext()));
            btnCycleView.setIconResource(R.drawable.ic_view_mode_list);
        } else {
            rvMyCourses.setLayoutManager(new LinearLayoutManager(getContext()));
            btnCycleView.setIconResource(R.drawable.ic_view_mode_large);
        }
    }
}
