package com.example.scholaria.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.models.Assignment;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.chip.ChipGroup;
import java.util.ArrayList;
import java.util.List;

public class AssignmentFragment extends Fragment {

    private RecyclerView rvAssignments;
    private AssignmentAdapter adapter;
    private MaterialButton btnCycleView;
    private ChipGroup chipGroupFilter;
    private List<Assignment> allAssignments = new ArrayList<>();
    private List<Assignment> displayedAssignments = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_assignment, container, false);

        rvAssignments = view.findViewById(R.id.rvAssignments);
        btnCycleView = view.findViewById(R.id.btnCycleView);
        chipGroupFilter = view.findViewById(R.id.chipGroupFilter);

        initData();
        setupRecyclerView();
        setupFilterChips();

        return view;
    }

    private void initData() {
        allAssignments.clear();
        // Upcoming
        allAssignments.add(new Assignment("Computer Programming 2", "Due: March 23, 2026 | 11:59 PM"));
        allAssignments.add(new Assignment("Systems Integration", "Due: March 30, 2026 | 11:59 PM"));
        allAssignments.add(new Assignment("Database Management", "Due: April 05, 2026 | 11:59 PM"));

        // Mocking some other states for the demo
        allAssignments.add(new Assignment("Web Systems", "Due: April 12, 2026 | 11:59 PM"));
        allAssignments.add(new Assignment("Data Structures", "Due: April 19, 2026 | 11:59 PM"));
    }

    private void setupRecyclerView() {
        displayedAssignments.addAll(allAssignments);
        adapter = new AssignmentAdapter(displayedAssignments);
        rvAssignments.setLayoutManager(new LinearLayoutManager(getContext()));
        rvAssignments.setAdapter(adapter);
    }

    private void setupFilterChips() {
        chipGroupFilter.setOnCheckedChangeListener((group, checkedId) -> {
            // Simplified filtering logic to match the look of Courses page
            filterAssignments(checkedId);
        });
    }

    private void filterAssignments(int checkedId) {
        // For now, we'll just show all or a subset to demonstrate the UI behavior
        displayedAssignments.clear();
        if (checkedId == R.id.chipUpcoming) {
            displayedAssignments.addAll(allAssignments);
        } else if (checkedId == R.id.chipOverdue) {
            // Empty for demo or add overdue items
        } else if (checkedId == R.id.chipCompleted) {
            // Empty for demo or add completed items
        }
        adapter.notifyDataSetChanged();
    }
}
