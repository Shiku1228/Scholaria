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
import com.example.scholaria.adapters.EventAdapter;
import com.example.scholaria.adapters.SubjectAdapter;
import com.example.scholaria.models.Assignment;
import com.example.scholaria.models.Event;
import com.example.scholaria.models.Subject;
import java.util.ArrayList;
import java.util.List;

public class DashboardFragment extends Fragment {

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_dashboard, container, false);

        setupSubjects(view);
        setupAssignments(view);
        setupEvents(view);

        return view;
    }

    private void setupSubjects(View v) {
        RecyclerView rv = v.findViewById(R.id.rvSubjects);
        List<Subject> list = new ArrayList<>();
        list.add(new Subject("Application Development", "CC106", "CN 48125"));
        list.add(new Subject("Web Systems", "WS067", "CN 48126"));
        list.add(new Subject("Data Structures", "CS201", "CN 48127"));

        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new SubjectAdapter(list));
    }

    private void setupAssignments(View v) {
        RecyclerView rv = v.findViewById(R.id.rvAssignments);
        List<Assignment> list = new ArrayList<>();
        list.add(new Assignment("Coding Task", "Computer Programming 2", "Due: March 23, 2026 | 11:59 PM"));
        list.add(new Assignment("System Design", "Systems Integration", "Due: March 30, 2026 | 11:59 PM"));
        list.add(new Assignment("ER Diagram", "Database Management", "Due: April 05, 2026 | 11:59 PM"));

        rv.setLayoutManager(new LinearLayoutManager(getContext()));
        rv.setAdapter(new AssignmentAdapter(list));
    }

    private void setupEvents(View v) {
        RecyclerView rv = v.findViewById(R.id.rvEvents);
        List<Event> list = new ArrayList<>();
        list.add(new Event("Midterm Exam", "MAR 05", "08:00 AM - 10:00 AM"));
        list.add(new Event("Project Demo", "MAR 12", "01:00 PM - 03:00 PM"));
        list.add(new Event("Tech Seminar", "MAR 20", "09:00 AM - 12:00 PM"));

        rv.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new EventAdapter(list));
    }
}
