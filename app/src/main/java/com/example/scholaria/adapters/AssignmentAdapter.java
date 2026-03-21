package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.Assignment;
import java.util.List;

public class AssignmentAdapter extends RecyclerView.Adapter<AssignmentAdapter.ViewHolder> {
    private List<Assignment> assignments;

    public AssignmentAdapter(List<Assignment> assignments) {
        this.assignments = assignments;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_assignment_card, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Assignment assignment = assignments.get(position);
        holder.tvName.setText(assignment.getName());
        holder.tvDue.setText(assignment.getDueDate());
    }

    @Override
    public int getItemCount() { return assignments.size(); }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvDue;
        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tvAssignmentName);
            tvDue = itemView.findViewById(R.id.tvAssignmentDue);
        }
    }
}
