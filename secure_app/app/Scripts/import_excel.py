import sys
import pandas as pd
import json
import re
from datetime import datetime
import os

def parse_row_metadata(row_str):
    """
    Extract metadata patterns from a single row string.
    """
    meta = {'tanggal': None, 'sesi': None, 'jam': None}
    
    # Pattern 1: Day, Date Month Year (e.g. rabu, 15 APRIL 2026)
    date_match = re.search(r'(\w+),\s*(\d{1,2})\s+(\w+)\s+(\d{4})', row_str, re.IGNORECASE)
    if date_match:
        day, day_num, month, year = date_match.groups()
        months = {
            'januari': '01', 'februari': '02', 'maret': '03', 'april': '04', 'mei': '05', 'juni': '06',
            'juli': '07', 'agustus': '08', 'september': '09', 'oktober': '10', 'november': '11', 'desember': '12',
            'january': '01', 'february': '02', 'march': '03', 'may': '05', 'june': '06',
            'july': '07', 'august': '08'
        }
        month_num = months.get(month.lower(), '01')
        meta['tanggal'] = f"{year}-{month_num}-{day_num.zfill(2)}"

    # Pattern 2: Session Info (e.g. Sesi 2)
    sesi_match = re.search(r'(Sesi\s*\d+)', row_str, re.IGNORECASE)
    if sesi_match:
        meta['sesi'] = sesi_match.group(1).upper()
        
    # Pattern 3: Time Range (e.g. 12.00-13.00)
    jam_match = re.search(r'(\d{2}\.\d{2}[-\s]*\d{2}\.\d{2})', row_str)
    if jam_match:
        meta['jam'] = jam_match.group(1).replace(' ', '')
        
    return meta

def process_excel(file_path):
    try:
        if not os.path.exists(file_path):
            return {'success': False, 'message': f'File not found: {file_path}'}

        # Try to read the file
        df_all = None
        try:
            for engine in ['openpyxl', 'xlrd']:
                try:
                    df_all = pd.read_excel(file_path, header=None, engine=engine)
                    break
                except: continue
            if df_all is None:
                df_all = pd.read_csv(file_path, header=None)
        except Exception as e:
            return {'success': False, 'message': f'Gagal membaca file: {str(e)}'}

        # Initial Metadata
        current_meta = {
            'tanggal': datetime.now().strftime('%Y-%m-%d'),
            'sesi': 'SESI 1',
            'jam': None
        }
        
        # Determine Mapping from any row that looks like a header
        mapping = {}
        header_row_found = False
        
        results = []
        
        for index, row in df_all.iterrows():
            row_values = [str(val).strip() for val in row.values]
            joined_row = " ".join(row_values).lower()
            
            # 1. Update Metadata if this looks like a metadata row
            new_meta = parse_row_metadata(" ".join(row_values))
            if new_meta['tanggal']: current_meta['tanggal'] = new_meta['tanggal']
            if new_meta['sesi']: current_meta['sesi'] = new_meta['sesi']
            if new_meta['jam']: current_meta['jam'] = new_meta['jam']
            
            # 2. Check if this is a header row to update mapping
            target_keywords = ['pesanan', 'nama', 'kode']
            matches = sum(1 for k in target_keywords if k in joined_row)
            if matches >= 2:
                # Update mapping based on this header row
                mapping = {}
                for i, val in enumerate(row_values):
                    v_low = val.lower().strip()
                    if 'pesanan' in v_low:
                        mapping[i] = 'no_pesanan'
                    elif 'nama' in v_low:
                        mapping[i] = 'nama_pemesan'
                    elif any(k in v_low for k in ['kode paket', 'no paket', 'no.paket', 'nomor paket', 'nomor', 'number']) or (v_low in ['no', 'no.']):
                        mapping[i] = 'no_paket'
                    elif v_low == 'kode' or (v_low == 'kode' and len(v_low) < 10):
                        mapping[i] = 'kode'
                header_row_found = True
                continue # Skip the header row itself
            
            # 3. If we have a mapping, try to process as data
            if header_row_found and mapping:
                no_pesanan = row_values[next((i for i, v in mapping.items() if v == 'no_pesanan'), -1)]
                nama_pemesan = row_values[next((i for i, v in mapping.items() if v == 'nama_pemesan'), -1)]
                
                # Validasi: Bukan header yang berulang & bukan metadata
                if not no_pesanan or no_pesanan.lower() in ['nan', '', 'none', 'no pesanan', 'pesanan', 'sesi']:
                    continue
                if any(day in no_pesanan.lower() for day in ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']):
                    continue
                if nama_pemesan.lower() in ['nama pemesan', 'nama']:
                    continue
                    
                # Formatting
                kode_idx = next((i for i, v in mapping.items() if v == 'kode'), -1)
                kode = row_values[kode_idx] if kode_idx != -1 else ''
                if kode.lower() in ['nan', 'none']: kode = ''
                
                no_paket_idx = next((i for i, v in mapping.items() if v == 'no_paket'), -1)
                no_paket = row_values[no_paket_idx] if no_paket_idx != -1 else ''
                if no_paket.lower() in ['nan', 'none']: no_paket = ''
                
                item = {
                    'no_paket': no_paket if no_paket else None,
                    'no_pesanan': no_pesanan,
                    'nama_pemesan': nama_pemesan.title(),
                    'kode': kode.upper(),
                    'sesi': current_meta['sesi'],
                    'tanggal': current_meta['tanggal'],
                    'jam': current_meta['jam']
                }
                results.append(item)
                
        return {
            'success': True,
            'count': len(results),
            'items': results
        }
        
    except Exception as e:
        import traceback
        return {'success': False, 'message': f'Error processing excel: {str(e)}', 'trace': traceback.format_exc()}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({'success': False, 'message': 'No file path provided'}))
        sys.exit(1)
        
    file_path = sys.argv[1]
    result = process_excel(file_path)
    print(json.dumps(result))
