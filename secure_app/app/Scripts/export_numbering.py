import sys
import json
import os
from openpyxl import Workbook
from openpyxl.styles import Font, Alignment, PatternFill, Border, Side

def export_to_excel(data, output_path):
    try:
        wb = Workbook()
        ws = wb.active
        ws.title = "Laporan Numbering Paket"

        # 1. Title section
        ws.merge_cells('A1:I1')
        ws['A1'] = "LAPORAN NUMBERING & PENOMORAN PAKET"
        ws['A1'].font = Font(size=16, bold=True, color="1E3A8A")
        ws['A1'].alignment = Alignment(horizontal="center", vertical="center")
        
        ws.merge_cells('A2:I2')
        ws['A2'] = f"Generated at: {data['generated_at']}"
        ws['A2'].font = Font(italic=True, color="4B5563")
        ws['A2'].alignment = Alignment(horizontal="center")

        # 2. Header Row
        headers = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "TANGGAL", "KODE", "SESI", "PETUGAS", "STATUS PILIHAN"]
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
        
        # Color definitions
        fill_selected = PatternFill(start_color="DCFCE7", end_color="DCFCE7", fill_type="solid") # Light Green
        font_selected = Font(color="166534", bold=True) # Dark Green
        
        fill_not_selected = PatternFill(start_color="FEE2E2", end_color="FEE2E2", fill_type="solid") # Light Red
        font_not_selected = Font(color="991B1B", bold=True) # Dark Red

        data_border = Border(left=Side(style='thin', color="CBD5E1"), 
                             right=Side(style='thin', color="CBD5E1"), 
                             top=Side(style='thin', color="CBD5E1"), 
                             bottom=Side(style='thin', color="CBD5E1"))

        for idx, item in enumerate(items, 1):
            row_idx = header_row + idx
            
            is_selected = item.get('is_selected', 0) == 1
            status_text = "TERPILIH" if is_selected else "BELUM TERPILIH"
            
            # Use nomor_urut for NO PAKET if selected, otherwise use original no_paket
            no_paket_display = f"({item.get('nomor_urut')}) {item.get('no_paket')}" if (is_selected and item.get('nomor_urut')) else item.get('no_paket')

            row_data = [
                idx,
                no_paket_display,
                item.get('no_pesanan', ''),
                item.get('nama_pemesan', ''),
                item.get('tanggal_display', ''),
                item.get('kode', ''),
                item.get('sesi', ''),
                item.get('petugas', '-'),
                status_text
            ]
            ws.append(row_data)
            
            # Style the row
            current_fill = fill_selected if is_selected else fill_not_selected
            current_font = font_selected if is_selected else font_not_selected

            for col_num in range(1, 10):
                cell = ws.cell(row=row_idx, column=col_num)
                cell.alignment = Alignment(horizontal="center", vertical="center")
                cell.border = data_border
                cell.fill = current_fill
                
                # Apply bold/color font to the last column (status) and NO PAKET
                if col_num in [2, 9]:
                    cell.font = current_font

        # 4. Stats Footer
        footer_start = ws.max_row + 2
        total_selected = sum(1 for item in items if item.get('is_selected', 0) == 1)
        
        ws.merge_cells(f'A{footer_start}:H{footer_start}')
        ws[f'A{footer_start}'] = "TOTAL PAKET TERPILIH"
        ws[f'I{footer_start}'] = f"{total_selected} Paket"
        ws[f'I{footer_start}'].font = Font(bold=True, color="166534")

        ws.merge_cells(f'A{footer_start+1}:H{footer_start+1}')
        ws[f'A{footer_start+1}'] = "TOTAL PAKET BELUM TERPILIH"
        ws[f'I{footer_start+1}'] = f"{len(items) - total_selected} Paket"
        ws[f'I{footer_start+1}'].font = Font(bold=True, color="991B1B")

        # 5. Column Adjustments
        dims = {
            'A': 5, 'B': 18, 'C': 20, 'D': 35, 'E': 15, 'F': 12, 'G': 12, 'H': 15, 'I': 18
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
