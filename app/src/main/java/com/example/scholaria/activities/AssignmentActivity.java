package com.example.scholaria.activities;

import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.scholaria.R;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.models.Assignment;

import java.util.ArrayList;
import java.util.List;

public class AssignmentActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_assignment); // HUWAG KALIMUTAN ITO!

        RecyclerView rv = findViewById(R.id.rvAssignments);

        // Sabihan ang RecyclerView na Vertical ang listahan
        rv.setLayoutManager(new LinearLayoutManager(this));

        List<Assignment> list = new ArrayList<>();

        // Sample data
        list.add(new Assignment("Final Project: Mobile App", "Application Development | CC106", "Oct 25"));
        list.add(new Assignment("Database Schema Design", "DBMS | CC104", "Oct 28"));
        list.add(new Assignment("Networking Quiz 1", "Net 1 | CC105", "Tomorrow"));

        AssignmentAdapter adapter = new AssignmentAdapter(list);
        rv.setAdapter(adapter);
    }
}
