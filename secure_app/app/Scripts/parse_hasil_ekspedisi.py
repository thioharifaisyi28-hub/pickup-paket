import sys
import pandas as pd
import json
import os
import re
from datetime import datetime

def process_excel(file_path):
    try:
        if not os.path.exists(file_path):
            return {'success': False, 'message': f'File not found: {file_path}'}

        ext = os.path.splitext(file_path)[1].lower()
        all_results = []
        
        try:
            if ext == '.csv':
                # CSV only has one "sheet"
                for sep in [',', ';', '\t']:
                    try:
                        df = pd.read_csv(file_path, sep=sep)
                        if len(df.columns) > 1:
                            all_results.extend(parse_dataframe(df, 'CSV'))
                            break
                    except: continue
            else:
                # Excel can have multiple sheets
                for engine in ['openpyxl', 'xlrd']:
                    try:
                        sheets_dict = pd.read_excel(file_path, engine=engine, sheet_name=None, header=None)
                        
                        for sheet_name, df_raw in sheets_dict.items():
                            if df_raw.empty: continue
                            
                            header_idx = -1
                            for i, row in df_raw.head(10).iterrows():
                                row_str = " ".join([str(x).lower() for x in row.values if pd.notnull(x)])
                                if any(k in row_str for k in ['resi', 'paket', 'waybill', 'penerima', 'nama', 'status', 'alamat']):
                                    header_idx = i
                                    break
                            
                            if header_idx != -1:
                                df = pd.read_excel(file_path, engine=engine, sheet_name=sheet_name, header=header_idx)
                            else:
                                df = pd.read_excel(file_path, engine=engine, sheet_name=sheet_name, header=None)
                            
                            all_results.extend(parse_dataframe(df, sheet_name))
                        break
                    except: continue
            
            if not all_results:
                return {'success': False, 'message': 'Tidak ada data yang valid ditemukan di semua sheet.'}
                
            return {
                'success': True,
                'count': len(all_results),
                'items': all_results
            }
            
        except Exception as e:
            return {'success': False, 'message': f'Gagal membaca file: {str(e)}'}

    except Exception as e:
        import traceback
        return {'success': False, 'message': f'Error processing excel: {str(e)}', 'trace': traceback.format_exc()}

def parse_dataframe(df, sheet_name):
    if df is None or df.empty:
        return []
        
    columns = df.columns.tolist()
    mapping = {}
    
    for col in columns:
        c_low = str(col).lower()
        if any(k in c_low for k in ['resi', 'paket', 'waybill', 'nomor']):
            if 'resi' not in mapping: mapping['resi'] = col
        elif any(k in c_low for k in ['nama', 'penerima', 'pelanggan', 'customer']):
            if 'nama_penerima' not in mapping: mapping['nama_penerima'] = col
        elif any(k in c_low for k in ['alamat', 'address', 'tujuan']):
            if 'alamat' not in mapping: mapping['alamat'] = col
        elif any(k in c_low for k in ['ekspedisi', 'kurir', 'logistic', 'vendor']):
            if 'ekspedisi' not in mapping: mapping['ekspedisi'] = col
        elif any(k in c_low for k in ['status', 'keterangan']):
            if 'status_scan' not in mapping: mapping['status_scan'] = col
        elif any(k in c_low for k in ['tanggal', 'waktu', 'date', 'time', 'upload']):
            if 'tanggal' not in mapping: mapping['tanggal'] = col

    if 'resi' not in mapping and len(columns) > 0:
        mapping['resi'] = columns[0]
    
    if len(columns) > 1 and 'nama_penerima' not in mapping: mapping['nama_penerima'] = columns[1]
    if len(columns) > 2 and 'alamat' not in mapping: mapping['alamat'] = columns[2]
    if len(columns) > 3 and 'ekspedisi' not in mapping: mapping['ekspedisi'] = columns[3]
    if len(columns) > 4 and 'status_scan' not in mapping: mapping['status_scan'] = columns[4]
    if len(columns) > 5 and 'tanggal' not in mapping: mapping['tanggal'] = columns[5]
        
    sheet_results = []
    for index, row in df.iterrows():
        resi_val = row[mapping['resi']] if 'resi' in mapping else None
        resi = str(resi_val).strip() if pd.notnull(resi_val) else ''
        
        if resi.endswith('.0'): resi = resi[:-2]
        
        if not resi or resi.lower() in ['nan', '', 'none', 'resi', 'nomor', 'no. paket', 'waybill']:
            continue
            
        item = {
            'resi': resi,
            'nama_penerima': str(row[mapping['nama_penerima']]) if 'nama_penerima' in mapping and pd.notnull(row[mapping['nama_penerima']]) else '',
            'alamat': str(row[mapping['alamat']]) if 'alamat' in mapping and pd.notnull(row[mapping['alamat']]) else '',
            'ekspedisi': str(row[mapping['ekspedisi']]) if 'ekspedisi' in mapping and pd.notnull(row[mapping['ekspedisi']]) else sheet_name,
            'status_scan': str(row[mapping['status_scan']]) if 'status_scan' in mapping and pd.notnull(row[mapping['status_scan']]) else 'Berhasil',
            'tanggal': str(row[mapping['tanggal']]) if 'tanggal' in mapping and pd.notnull(row[mapping['tanggal']]) else datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'sheet_name': sheet_name
        }
        
        for key in item:
            if str(item[key]).lower() in ['nan', 'none']:
                item[key] = ''
        
        sheet_results.append(item)
    
    return sheet_results

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({'success': False, 'message': 'Missing arguments: input_file output_file'}))
        sys.exit(1)
        
    file_path = sys.argv[1]
    out_path = sys.argv[2]
    
    result = process_excel(file_path)
    
    try:
        with open(out_path, 'w', encoding='utf-8') as f:
            json.dump(result, f, ensure_ascii=False)
        print("SUCCESS")
    except Exception as e:
        print(json.dumps({'success': False, 'message': f'Failed to write output: {str(e)}'}))
