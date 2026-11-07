import sys
import pandas as pd
import json
import os
from openpyxl import Workbook
from openpyxl.styles import Font, Alignment, PatternFill, Border, Side
from openpyxl.utils import get_column_letter

def export_to_excel(data, output_path):
    try:
        wb = Workbook()
        ws = wb.active
        ws.title = "Laporan Pickup Paket"

        # 1. Title section
        ws.merge_cells('A1:J1')
        ws['A1'] = "LAPORAN REKAPITULASI PICKUP PAKET"
        ws['A1'].font = Font(size=16, bold=True, color="1E3A8A")
        ws['A1'].alignment = Alignment(horizontal="center", vertical="center")
        
        ws.merge_cells('A2:J2')
        ws['A2'] = f"Generated at: {data['generated_at']}"
        ws['A2'].font = Font(italic=True, color="4B5563")
        ws['A2'].alignment = Alignment(horizontal="center")

        # 2. Header Row
        headers = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "TANGGAL UPDATE", "KODE", "SESI", "JAM", "STATUS", "PETUGAS"]
        header_fill = PatternFill(start_color="1E3A8A", end_color="1E3A8A", fill_type="solid")
        header_font = Font(color="FFFFFF", bold=True)
        thin_border = Border(left=Side(style='thin', color="FFFFFF"), 
                            right=Side(style='thin', color="FFFFFF"), 
                            top=Side(style='thin', color="FFFFFF"), 
                            bottom=Side(style='thin', color="FFFFFF"))

        ws.append([]) # Spacer row 3
        
        ws.append(headers)
        header_row = 4
        for col_num, header in enumerate(headers, 1):
            cell = ws.cell(row=header_row, column=col_num)
            cell.fill = header_fill
            cell.font = header_font
            cell.alignment = Alignment(horizontal="center", vertical="center")
            cell.border = thin_border

        # 3. Data Rows
        items = data['items']
        even_fill = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid")
        data_border = Border(left=Side(style='thin', color="CBD5E1"), 
                             right=Side(style='thin', color="CBD5E1"), 
                             top=Side(style='thin', color="CBD5E1"), 
                             bottom=Side(style='thin', color="CBD5E1"))

        stats = {'Sudah': 0, 'Batal': 0, 'Retur': 0, 'Pending': 0}
        
        for idx, item in enumerate(items, 1):
            row_idx = header_row + idx
            
            # Status based coloring
            status = item.get('status', 'Pending')
            stats[status] = stats.get(status, 0) + 1
            status_color = "64748B" # Default grey
            if status == "Sudah": status_color = "16A34A" # Green
            elif status in ["Batal", "Retur"]: status_color = "DC2626" # Red
            
            row_data = [
                idx,
                item.get('no_paket', ''),
                item.get('no_pesanan', ''),
                item.get('nama_pemesan', ''),
                item.get('tanggal_display', ''),
                item.get('kode', ''),
                item.get('sesi', ''),
                item.get('jam', '-'),
                status,
                item.get('input_by', '-')
            ]
            ws.append(row_data)
            
            for col_num in range(1, 11):
                cell = ws.cell(row=row_idx, column=col_num)
                cell.alignment = Alignment(vertical="center")
                cell.border = data_border
                if idx % 2 == 0:
                    cell.fill = even_fill
                
                # Center all columns for professional consistency
                cell.alignment = Alignment(horizontal="center", vertical="center")
                
                # Color status cell
                if col_num == 9:
                    cell.font = Font(bold=True, color=status_color)

        # 4. Footer / Stats
        footer_start = ws.max_row + 2
        
        ws.merge_cells(f'A{footer_start}:H{footer_start}')
        ws[f'A{footer_start}'] = "TOTAL DATA PAKET"
        ws[f'I{footer_start}'] = f"{len(items)} Data"
        
        ws.merge_cells(f'A{footer_start+1}:H{footer_start+1}')
        ws[f'A{footer_start+1}'] = "TOTAL PAKET DIAMBIL (SUDAH)"
        ws[f'I{footer_start+1}'] = stats['Sudah']
        ws[f'I{footer_start+1}'].font = Font(bold=True, color="16A34A")
        
        ws.merge_cells(f'A{footer_start+2}:H{footer_start+2}')
        ws[f'A{footer_start+2}'] = "TOTAL PAKET BATAL / RETUR"
        ws[f'I{footer_start+2}'] = stats['Batal'] + stats['Retur']
        ws[f'I{footer_start+2}'].font = Font(bold=True, color="DC2626")

        # 5. Column Adjustments
        dims = {
            'A': 5, 'B': 12, 'C': 20, 'D': 35, 'E': 22, 'F': 12, 'G': 10, 'H': 15, 'I': 12, 'J': 15
        }
        for col, value in dims.items():
            ws.column_dimensions[col].width = value

        wb.save(output_path)
        return True
    except Exception as e:
        print(f"Error generating Excel: {str(e)}")
        return False

if __name__ == "__main__":
    if len(sys.argv) < 3:
        sys.exit(1)
        
    json_path = sys.argv[1]
    output_path = sys.argv[2]
    
    with open(json_path, 'r') as f:
        data = json.load(f)
        
    success = export_to_excel(data, output_path)
    if success:
        sys.exit(0)
    else:
        sys.exit(1)
