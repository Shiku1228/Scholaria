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
import com.example.scholaria.adapters.CourseAdapter;
import com.example.scholaria.models.Course;
import com.example.scholaria.networks.ApiClient;
import com.example.scholaria.networks.StudentApiResponse;
import com.example.scholaria.networks.StudentCourseDto;
import com.google.android.material.button.MaterialButton;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CoursesFragment extends Fragment {

    private RecyclerView rvMyCourses;
    private CourseAdapter adapter;
    private MaterialButton btnCycleView;
    private int currentViewMode = CourseAdapter.VIEW_TYPE_LARGE;

    private final List<Course> allCoursesList = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_courses, container, false);

        rvMyCourses = view.findViewById(R.id.rvMyCourses);
        btnCycleView = view.findViewById(R.id.btnCycleView);

        setupRecyclerView();
        setupViewSwitchers();
        loadCourses();

        return view;
    }

    private void setupRecyclerView() {
        adapter = new CourseAdapter(allCoursesList);
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

    private void loadCourses() {
        if (getContext() == null) {
            return;
        }

        ApiClient.getInstance(requireContext())
                .getStudentCoursesCall()
                .enqueue(new Callback<StudentApiResponse<List<StudentCourseDto>>>() {
                    @Override
                    public void onResponse(@NonNull Call<StudentApiResponse<List<StudentCourseDto>>> call, @NonNull Response<StudentApiResponse<List<StudentCourseDto>>> response) {
                        if (!isAdded()) {
                            return;
                        }

                        if (!response.isSuccessful() || response.body() == null || response.body().getData() == null) {
                            showToast("Unable to load courses.");
                            return;
                        }

                        bindCourses(response.body().getData());
                    }

                    @Override
                    public void onFailure(@NonNull Call<StudentApiResponse<List<StudentCourseDto>>> call, @NonNull Throwable t) {
                        if (isAdded()) {
                            showToast("Course sync failed.");
                        }
                    }
                });
    }

    private void bindCourses(List<StudentCourseDto> data) {
        allCoursesList.clear();
        for (StudentCourseDto dto : data) {
            allCoursesList.add(new Course(
                    String.valueOf(dto.getCourseId()),
                    safe(dto.getCourseName(), "Course"),
                    safe(dto.getCourseNumber(), "Code"),
                    safe(dto.getSemester(), "Semester"),
                    safe(dto.getSchoolYear(), "School Year"),
                    dto.getProgress(),
                    dto.getAssignmentsSubmitted(),
                    dto.getAssignmentsTotal(),
                    safe(dto.getTeacherName(), "Instructor"),
                    safe(dto.getEnrollmentStatus(), "Enrolled"),
                    R.drawable.course_banner_placeholder
            ));
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

    private void showToast(String message) {
        if (getContext() != null) {
            Toast.makeText(getContext(), message, Toast.LENGTH_SHORT).show();
        }
    }

    private String safe(String value, String fallback) {
        return value == null || value.trim().isEmpty() ? fallback : value;
    }
}
